<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\MainVariantGroup;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Component\EntityExtension\QueryBuilder;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibility;
use Shopsys\ShopBundle\Model\Product\Product;

class MainVariantGroupRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository|\Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupRepository
     */
    protected function getMainVariantGroupRepository(): EntityRepository
    {
        return $this->entityManager->getRepository(MainVariantGroup::class);
    }

    /**
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getProductsQueryBuilder(int $domainId, PricingGroup $pricingGroup): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->join(ProductVisibility::class, 'prv', Join::WITH, 'prv.product = p.id')
            ->where('prv.domainId = :domainId')
            ->andWhere('prv.pricingGroup = :pricingGroup')
            ->andWhere('prv.visible = TRUE')
            ->andWhere('p.sellingDenied = false')
            ->setParameter('domainId', $domainId)
            ->setParameter('pricingGroup', $pricingGroup);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getProductsForMainVariantGroup(Product $product, int $domainId, PricingGroup $pricingGroup): array
    {
        return $this->getProductsQueryBuilder($domainId, $pricingGroup)
            ->andWhere('p.mainVariantGroup = :mainVariantGroup')
            ->setParameter('mainVariantGroup', $product->getMainVariantGroup())
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter $parameter
     * @return \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup[]
     */
    public function getByDistinguishingParameter(Parameter $parameter): array
    {
        return $this->getMainVariantGroupRepository()->findBy([
            'distinguishingParameter' => $parameter,
        ]);
    }

    /**
     * @param array $mainVariantGroups
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getProductsForMainVariantGroups(array $mainVariantGroups, int $domainId, PricingGroup $pricingGroup): array
    {
        return $this->getProductsQueryBuilder($domainId, $pricingGroup)
            ->andWhere('p.mainVariantGroup IN (:mainVariantGroups)')
            ->setParameter('mainVariantGroups', $mainVariantGroups)
            ->getQuery()
            ->getResult();
    }
}
