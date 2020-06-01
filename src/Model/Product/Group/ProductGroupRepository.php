<?php

declare(strict_types=1);

namespace App\Model\Product\Group;

use App\Model\Pricing\Group\PricingGroup;
use App\Model\Product\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibility;

class ProductGroupRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getProductGroupRepository(): EntityRepository
    {
        return $this->em->getRepository(ProductGroup::class);
    }

    /**
     * @param \App\Model\Product\Product $mainProduct
     * @return \App\Model\Product\Group\ProductGroup[]
     */
    public function getAllByMainProduct(Product $mainProduct): array
    {
        return $this->getProductGroupRepository()->findBy(['mainProduct' => $mainProduct]);
    }

    /**
     * @param \App\Model\Product\Product $item
     * @return \App\Model\Product\Group\ProductGroup[]
     */
    public function getAllByItem(Product $item): array
    {
        return $this->getProductGroupRepository()->findBy(['item' => $item]);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Group\ProductGroup[]
     */
    public function getVisibleByItem(Product $product, int $domainId, PricingGroup $pricingGroup): array
    {
        return $this->em->createQueryBuilder()
            ->select('pg')
            ->from(ProductGroup::class, 'pg')
            ->join(ProductVisibility::class, 'prv', Join::WITH, 'prv.product = pg.mainProduct')
            ->where('prv.domainId = :domainId')
            ->andWhere('prv.pricingGroup = :pricingGroup')
            ->andWhere('prv.visible = TRUE')
            ->andWhere('pg.item = :item')
            ->setParameter('domainId', $domainId)
            ->setParameter('pricingGroup', $pricingGroup)
            ->setParameter('item', $product)
            ->getQuery()->execute();
    }
}
