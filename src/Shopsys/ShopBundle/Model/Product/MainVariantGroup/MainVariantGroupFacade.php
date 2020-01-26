<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\MainVariantGroup;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade;

class MainVariantGroupFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupRepository
     */
    private $mainVariantGroupRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer
     */
    private $currentCustomer;

    /**
     * @var \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupRepository $mainVariantGroupRepository
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        MainVariantGroupRepository $mainVariantGroupRepository,
        Domain $domain,
        CurrentCustomer $currentCustomer,
        PricingGroupFacade $pricingGroupFacade
    ) {
        $this->entityManager = $entityManager;
        $this->mainVariantGroupRepository = $mainVariantGroupRepository;
        $this->domain = $domain;
        $this->currentCustomer = $currentCustomer;
        $this->pricingGroupFacade = $pricingGroupFacade;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\Parameter $distinguishingParameter
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $products
     * @return \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup
     */
    public function createMainVariantGroup(Parameter $distinguishingParameter, array $products): MainVariantGroup
    {
        $mainVariantGroup = new MainVariantGroup($distinguishingParameter);
        $this->entityManager->persist($mainVariantGroup);

        foreach ($products as $product) {
            if ($product->isVariant() === false && $product->getMainVariantGroup() === null) {
                $product->setMainVariantGroup($mainVariantGroup);
            }
        }

        $this->entityManager->flush();

        return $mainVariantGroup;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup $mainVariantGroup
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $products
     * @return \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup
     */
    public function updateMainVariantGroup(MainVariantGroup $mainVariantGroup, array $products): MainVariantGroup
    {
        foreach ($products as $product) {
            if ($product->isVariant() === false) {
                $product->setMainVariantGroup($mainVariantGroup);
            }
        }

        $this->entityManager->flush();

        return $mainVariantGroup;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getProductsForMainVariantGroup(Product $product): array
    {
        return $this->mainVariantGroupRepository->getProductsForMainVariantGroup(
            $product,
            $this->domain->getId(),
            $this->currentCustomer->getPricingGroup()
        );
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param int $domainId
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getProductsForMainVariantGroupByProductAndDomainIdAndPricingGroup(Product $product, int $domainId, PricingGroup $pricingGroup): array
    {
        return $this->mainVariantGroupRepository->getProductsForMainVariantGroup(
            $product,
            $domainId,
            $pricingGroup
        );
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\Parameter $parameter
     * @return \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup[]
     */
    public function getByDistinguishingParameter(Parameter $parameter): array
    {
        return $this->mainVariantGroupRepository->getByDistinguishingParameter($parameter);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $products
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Product\Product[][][]
     */
    public function getProductsIndexedByPricingGroupIdAndMainVariantGroup(array $products, int $domainId): array
    {
        $productsIndexedByPricingGroupIdAndMainVariantGroup = [];
        foreach ($this->pricingGroupFacade->getByDomainId($domainId) as $pricingGroup) {
            $productsIndexedByPricingGroupIdAndMainVariantGroup[$pricingGroup->getId()] = $this->getProductsIndexedByMainVariantGroup(
                $products,
                $pricingGroup
            );
        }

        return $productsIndexedByPricingGroupIdAndMainVariantGroup;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $products
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Shopsys\ShopBundle\Model\Product\Product[][]
     */
    public function getProductsIndexedByMainVariantGroup(array $products, PricingGroup $pricingGroup): array
    {
        $mainVariantGroups = [];
        foreach ($products as $product) {
            if ($product->getMainVariantGroup() !== null) {
                $mainVariantGroups[$product->getMainVariantGroup()->getId()] = $product->getMainVariantGroup();
            }
        }

        $allProductsInMainVariantGroups = $this->mainVariantGroupRepository->getProductsForMainVariantGroups(
            $mainVariantGroups,
            $pricingGroup->getDomainId(),
            $pricingGroup
        );

        $mainVariantGroups = [];
        foreach ($allProductsInMainVariantGroups as $product) {
            $mainVariantGroups[$product->getMainVariantGroup()->getId()][] = $product;
        }

        return $mainVariantGroups;
    }
}
