<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Parameter;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterData;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFacade as BaseParameterFacade;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue;
use Shopsys\ShopBundle\Model\Product\CachedProductDistinguishingParameterValueFacade;
use Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade;
use Shopsys\ShopBundle\Model\Product\Parameter\Exception\ParameterUsedAsDistinguishingParameterException;
use Shopsys\ShopBundle\Model\Product\ProductFacade;

class ParameterFacade extends BaseParameterFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade
     */
    private $mainVariantGroupFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterRepository
     */
    protected $parameterRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterDataFactoryInterface
     */
    private $parameterDataFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\CachedProductDistinguishingParameterValueFacade
     */
    private $cachedProductDistinguishingParameterValueFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFactoryInterface $parameterFactory
     * @param \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade $mainVariantGroupFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterDataFactoryInterface $parameterDataFactory
     * @param \Shopsys\ShopBundle\Model\Product\CachedProductDistinguishingParameterValueFacade $cachedProductDistinguishingParameterValueFacade
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
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterData $parameterData
     * @return \Shopsys\ShopBundle\Model\Product\Parameter\Parameter
     */
    public function edit($parameterId, ParameterData $parameterData)
    {
        /** @var \Shopsys\ShopBundle\Model\Product\Parameter\Parameter $parameterForCheck */
        $parameterForCheck = $this->parameterRepository->getById($parameterId);

        if ($parameterData->visibleOnFrontend !== $parameterForCheck->isVisibleOnFrontend() && count($this->getProductsWithDistinguishingParameter($parameterForCheck)) > 0) {
            throw new ParameterUsedAsDistinguishingParameterException();
        }

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
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter $parameter
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
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
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter
     */
    public function findOrCreateParameterByNames(array $parameterNamesByLocale): Parameter
    {
        $parameter = $this->findParameterByNames($parameterNamesByLocale);

        if ($parameter === null) {
            $parameterData = $this->parameterDataFactory->create();
            $parameterData->name = $parameterNamesByLocale;
            $parameterData->visible = true;
            $parameter = $this->create($parameterData);
        }

        return $parameter;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter|null
     */
    public function findColorParameter(): ?Parameter
    {
        return $this->findParameterByNames([
            'cs' => 'Barva',
            'sk' => 'Farba',
            'de' => 'Farbe',
        ]);
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter|null
     */
    public function findSizeParameter(): ?Parameter
    {
        return $this->findParameterByNames([
            'cs' => 'Velikost',
            'sk' => 'Velikosť',
            'de' => 'Größe',
        ]);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter $parameter
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
}
