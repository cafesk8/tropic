<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Parameter;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterDataFactory;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFacade as BaseParameterFacade;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository;
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
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterDataFactoryInterface
     */
    private $parameterDataFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFactoryInterface $parameterFactory
     * @param \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade $mainVariantGroupFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterDataFactoryInterface $parameterDataFactory
     */
    public function __construct(EntityManagerInterface $em, ParameterRepository $parameterRepository, ParameterFactoryInterface $parameterFactory, MainVariantGroupFacade $mainVariantGroupFacade, ProductFacade $productFacade, ParameterDataFactoryInterface $parameterDataFactory)
    {
        parent::__construct($em, $parameterRepository, $parameterFactory);
        $this->mainVariantGroupFacade = $mainVariantGroupFacade;
        $this->productFacade = $productFacade;
        $this->parameterDataFactory = $parameterDataFactory;
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
}
