<?php

declare(strict_types=1);

namespace App\Model\Product\MainVariantGroup;

use App\Model\Pricing\Group\PricingGroupFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportScheduler;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Product;

class MainVariantGroupFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var \App\Model\Product\MainVariantGroup\MainVariantGroupRepository
     */
    private $mainVariantGroupRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser
     */
    private $currentCustomerUser;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportScheduler
     */
    private $productExportScheduler;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \App\Model\Product\MainVariantGroup\MainVariantGroupRepository $mainVariantGroupRepository
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportScheduler $productExportScheduler
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        MainVariantGroupRepository $mainVariantGroupRepository,
        Domain $domain,
        CurrentCustomerUser $currentCustomerUser,
        PricingGroupFacade $pricingGroupFacade,
        ProductExportScheduler $productExportScheduler
    ) {
        $this->entityManager = $entityManager;
        $this->mainVariantGroupRepository = $mainVariantGroupRepository;
        $this->domain = $domain;
        $this->currentCustomerUser = $currentCustomerUser;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->productExportScheduler = $productExportScheduler;
    }

    /**
     * @param \App\Model\Product\Parameter\Parameter $distinguishingParameter
     * @param \App\Model\Product\Product[] $products
     * @return \App\Model\Product\MainVariantGroup\MainVariantGroup
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
        $this->scheduleForImmediateExport($products);

        return $mainVariantGroup;
    }

    /**
     * @param \App\Model\Product\MainVariantGroup\MainVariantGroup $mainVariantGroup
     * @param \App\Model\Product\Product[] $products
     * @return \App\Model\Product\MainVariantGroup\MainVariantGroup
     */
    public function updateMainVariantGroup(MainVariantGroup $mainVariantGroup, array $products): MainVariantGroup
    {
        foreach ($products as $product) {
            if ($product->isVariant() === false) {
                $product->setMainVariantGroup($mainVariantGroup);
            }
        }

        $this->entityManager->flush();
        $this->scheduleForImmediateExport($products);

        return $mainVariantGroup;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\Product[]
     */
    public function getProductsForMainVariantGroup(Product $product): array
    {
        return $this->mainVariantGroupRepository->getProductsForMainVariantGroup(
            $product,
            $this->domain->getId(),
            $this->currentCustomerUser->getPricingGroup()
        );
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Product[]
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
     * @param \App\Model\Product\Parameter\Parameter $parameter
     * @return \App\Model\Product\MainVariantGroup\MainVariantGroup[]
     */
    public function getByDistinguishingParameter(Parameter $parameter): array
    {
        return $this->mainVariantGroupRepository->getByDistinguishingParameter($parameter);
    }

    /**
     * @param \App\Model\Product\Product[] $products
     * @param int $domainId
     * @return \App\Model\Product\Product[][][]
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
     * @param \App\Model\Product\Product[] $products
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Product[][]
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

    /**
     * @param \App\Model\Product\Product[] $products
     */
    private function scheduleForImmediateExport(array $products): void
    {
        foreach ($products as $product) {
            $this->productExportScheduler->scheduleRowIdForImmediateExport($product->getId());
        }
    }
}
