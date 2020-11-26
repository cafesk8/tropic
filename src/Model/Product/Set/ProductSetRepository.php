<?php

declare(strict_types=1);

namespace App\Model\Product\Set;

use App\Model\Pricing\Group\PricingGroup;
use App\Model\Product\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibility;

class ProductSetRepository
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
    protected function getProductSetRepository(): EntityRepository
    {
        return $this->em->getRepository(ProductSet::class);
    }

    /**
     * @param \App\Model\Product\Product $mainProduct
     * @return \App\Model\Product\Set\ProductSet[]
     */
    public function getAllByMainProduct(Product $mainProduct): array
    {
        return $this->getProductSetRepository()->findBy(['mainProduct' => $mainProduct]);
    }

    /**
     * @param \App\Model\Product\Product $item
     * @return \App\Model\Product\Set\ProductSet[]
     */
    public function getAllByItem(Product $item): array
    {
        return $this->getProductSetRepository()->findBy(['item' => $item]);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getVisibleByItemQueryBuilder(Product $product, int $domainId, PricingGroup $pricingGroup): QueryBuilder
    {

        $itemIds = [$product->getId()];
        // find product sets also by product variants
        if($product->isMainVariant() && $product->getVariantsCount($domainId) > 0){
            foreach ($product->getVariants() as $variant) {
                $itemIds[] = $variant->getId();
            }
        }

        return $this->em->createQueryBuilder()
            ->select('pg')
            ->from(ProductSet::class, 'pg')
            ->join(ProductVisibility::class, 'prv', Join::WITH, 'prv.product = pg.mainProduct')
            ->where('prv.domainId = :domainId')
            ->andWhere('prv.pricingGroup = :pricingGroup')
            ->andWhere('prv.visible = TRUE')
            ->andWhere('pg.item IN (:itemIds)')
            ->setParameter('domainId', $domainId)
            ->setParameter('pricingGroup', $pricingGroup)
            ->setParameter('itemIds', $itemIds);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Set\ProductSet[]
     */
    public function getOfferedByItem(Product $product, int $domainId, PricingGroup $pricingGroup): array
    {
        return $this->getVisibleByItemQueryBuilder($product, $domainId, $pricingGroup)
            ->join(Product::class, 'p', Join::WITH, 'p = pg.mainProduct')
            ->andWhere('p.calculatedSellingDenied = FALSE')
            ->getQuery()->execute();
    }
}
