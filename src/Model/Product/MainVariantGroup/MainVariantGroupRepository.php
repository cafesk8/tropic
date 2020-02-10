<?php

declare(strict_types=1);

namespace App\Model\Product\MainVariantGroup;

use App\Model\Product\Product;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibility;

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
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getMainVariantGroupRepository(): ObjectRepository
    {
        return $this->entityManager->getRepository(MainVariantGroup::class);
    }

    /**
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
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
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Product[]
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
     * @param \App\Model\Product\Parameter\Parameter $parameter
     * @return \App\Model\Product\MainVariantGroup\MainVariantGroup[]
     */
    public function getByDistinguishingParameter(Parameter $parameter): array
    {
        return $this->getMainVariantGroupRepository()->findBy([
            'distinguishingParameter' => $parameter,
        ]);
    }

    /**
     * @param \App\Model\Product\MainVariantGroup\MainVariantGroup[] $mainVariantGroups
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Product[]
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
