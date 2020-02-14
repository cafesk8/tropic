<?php

declare(strict_types=1);

namespace App\Model\Product\Parameter;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter as BaseParameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterData;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFacade as BaseParameterFacade;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue;
use App\Model\Product\CachedProductDistinguishingParameterValueFacade;
use App\Model\Product\MainVariantGroup\MainVariantGroupFacade;
use App\Model\Product\Parameter\Exception\ParameterUsedAsDistinguishingParameterException;
use App\Model\Product\ProductFacade;

/**
 * @method \App\Model\Product\Parameter\Parameter getById(int $parameterId)
 * @method \App\Model\Product\Parameter\Parameter[] getAll()
 * @method \App\Model\Product\Parameter\Parameter create(\App\Model\Product\Parameter\ParameterData $parameterData)
 * @method \App\Model\Product\Parameter\Parameter|null findParameterByNames(string[] $namesByLocale)
 * @method \App\Model\Product\Parameter\ParameterValue getParameterValueByValueTextAndLocale(string $valueText, string $locale)
 */
class ParameterFacade extends BaseParameterFacade
{
    public const PARAMETER_COLOR = [
        'cs' => 'Barva',
        'sk' => 'Farba',
        'de' => 'Farbe',
    ];

    public const PARAMETER_SIZE = [
        'cs' => 'Velikost',
        'sk' => 'Velikosť',
        'de' => 'Größe',
    ];

    /**
     * @var \App\Model\Product\MainVariantGroup\MainVariantGroupFacade
     */
    private $mainVariantGroupFacade;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Model\Product\Parameter\ParameterRepository
     */
    protected $parameterRepository;

    /**
     * @var \App\Model\Product\Parameter\ParameterDataFactory
     */
    private $parameterDataFactory;

    /**
     * @var \App\Model\Product\CachedProductDistinguishingParameterValueFacade
     */
    private $cachedProductDistinguishingParameterValueFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFactoryInterface $parameterFactory
     * @param \App\Model\Product\MainVariantGroup\MainVariantGroupFacade $mainVariantGroupFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\Parameter\ParameterDataFactory $parameterDataFactory
     * @param \App\Model\Product\CachedProductDistinguishingParameterValueFacade $cachedProductDistinguishingParameterValueFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        ParameterRepository $parameterRepository,
        ParameterFactoryInterface $parameterFactory,
        MainVariantGroupFacade $mainVariantGroupFacade,
        ProductFacade $productFacade,
        ParameterDataFactoryInterface $parameterDataFactory,
        CachedProductDistinguishingParameterValueFacade $cachedProductDistinguishingParameterValueFacade
    ) {
        parent::__construct($em, $parameterRepository, $parameterFactory);

        $this->mainVariantGroupFacade = $mainVariantGroupFacade;
        $this->productFacade = $productFacade;
        $this->parameterDataFactory = $parameterDataFactory;
        $this->cachedProductDistinguishingParameterValueFacade = $cachedProductDistinguishingParameterValueFacade;
    }

    /**
     * @param int $parameterId
     * @param \App\Model\Product\Parameter\ParameterData $parameterData
     * @return \App\Model\Product\Parameter\Parameter
     */
    public function edit($parameterId, ParameterData $parameterData)
    {
        /** @var \App\Model\Product\Parameter\Parameter $parameterForCheck */
        $parameterForCheck = $this->parameterRepository->getById($parameterId);

        if ($parameterData->visibleOnFrontend !== $parameterForCheck->isVisibleOnFrontend() && count($this->getProductsWithDistinguishingParameter($parameterForCheck)) > 0) {
            throw new ParameterUsedAsDistinguishingParameterException();
        }

        /** @var \App\Model\Product\Parameter\Parameter $parameter */
        $parameter = parent::edit($parameterId, $parameterData);

        $this->cachedProductDistinguishingParameterValueFacade->invalidAll();

        return $parameter;
    }

    /**
     * @param int $parameterId
     */
    public function deleteById($parameterId)
    {
        $parameter = $this->parameterRepository->getById($parameterId);

        if (count($this->getProductsWithDistinguishingParameter($parameter)) > 0) {
            throw new ParameterUsedAsDistinguishingParameterException();
        }

        $this->em->remove($parameter);
        $this->em->flush();

        $this->cachedProductDistinguishingParameterValueFacade->invalidAll();
    }

    /**
     * @param \App\Model\Product\Parameter\Parameter $parameter
     * @return \App\Model\Product\Product[]
     */
    public function getProductsWithDistinguishingParameter(Parameter $parameter): array
    {
        return array_merge(
            $this->productFacade->getProductsWithDistinguishingParameter($parameter),
            $this->mainVariantGroupFacade->getByDistinguishingParameter($parameter)
        );
    }

    /**
     * @param int $productId
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue|null
     */
    public function findColorProductParameterValueByProductId(int $productId): ?ProductParameterValue
    {
        $parameter = $this->findColorParameter();

        if ($parameter === null) {
            return null;
        }

        $product = $this->productFacade->getById($productId);

        return $this->parameterRepository->findProductParameterValueByParameterAndProduct($parameter, $product);
    }

    /**
     * @param int $productId
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue|null
     */
    public function findSizeProductParameterValueByProductId(int $productId): ?ProductParameterValue
    {
        $parameter = $this->findSizeParameter();

        if ($parameter === null) {
            return null;
        }

        $product = $this->productFacade->getById($productId);

        return $this->parameterRepository->findProductParameterValueByParameterAndProduct($parameter, $product);
    }

    /**
     * @param string[] $parameterNamesByLocale
     * @param string|null $type
     * @param string|null $mallId
     * @param bool $visible
     * @param bool $visibleOnFrontend
     * @return \App\Model\Product\Parameter\Parameter
     */
    public function findOrCreateParameterByNames(
        array $parameterNamesByLocale,
        ?string $type = Parameter::TYPE_DEFAULT,
        ?string $mallId = null,
        bool $visible = true,
        bool $visibleOnFrontend = true
    ): BaseParameter {
        $parameter = $this->findParameterByNames($parameterNamesByLocale);

        if ($parameter === null) {
            /** @var \App\Model\Product\Parameter\ParameterData $parameterData */
            $parameterData = $this->parameterDataFactory->create();
            $parameterData->name = $parameterNamesByLocale;
            $parameterData->visible = $visible;
            $parameterData->type = $type;
            $parameterData->mallId = $mallId;
            $parameterData->visibleOnFrontend = $visibleOnFrontend;
            $parameter = $this->create($parameterData);
        }

        return $parameter;
    }

    /**
     * @return \App\Model\Product\Parameter\Parameter|null
     */
    public function findColorParameter(): ?BaseParameter
    {
        return $this->findParameterByNames(self::PARAMETER_COLOR);
    }

    /**
     * @return \App\Model\Product\Parameter\Parameter|null
     */
    public function findSizeParameter(): ?BaseParameter
    {
        return $this->findParameterByNames(self::PARAMETER_SIZE);
    }

    /**
     * @return \App\Model\Product\Parameter\Parameter
     */
    public function getColorParameter(): Parameter
    {
        return $this->findOrCreateParameterByNames(self::PARAMETER_COLOR, Parameter::TYPE_COLOR);
    }

    /**
     * @return \App\Model\Product\Parameter\Parameter
     */
    public function getSizeParameter(): Parameter
    {
        return $this->findOrCreateParameterByNames(self::PARAMETER_SIZE, Parameter::TYPE_SIZE);
    }

    /**
     * @param \App\Model\Product\Parameter\Parameter $parameter
     * @return string
     */
    public function getParameterUsedAsDistinguishingParameterExceptionProducts(Parameter $parameter): string
    {
        $productIdsWithDistinguishingParameter = implode(
            ', ',
            array_map(function ($product) {
                return $product->getId();
            }, $this->getProductsWithDistinguishingParameter($parameter))
        );

        return $productIdsWithDistinguishingParameter;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return array|\App\Model\Product\Parameter\ParameterValue[]
     */
    public function getParameterValuesBatch(int $limit, int $offset): array
    {
        return $this->parameterRepository->getParameterValuesBatch($limit, $offset);
    }

    /**
     * @param int $id
     * @return \App\Model\Product\Parameter\ParameterValue
     */
    public function getParameterValueById(int $id): ParameterValue
    {
        return $this->parameterRepository->getParameterValueById($id);
    }

    /**
     * @param \App\Model\Product\Parameter\ParameterValue $parameterValue
     * @param \App\Model\Product\Parameter\ParameterValueData $parameterValueData
     * @return \App\Model\Product\Parameter\ParameterValue
     */
    public function editParameterValue(ParameterValue $parameterValue, ParameterValueData $parameterValueData): ParameterValue
    {
        $parameterValue->edit($parameterValueData);
        $this->em->flush();

        return $parameterValue;
    }
}
