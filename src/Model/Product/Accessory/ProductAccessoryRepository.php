<?php

declare(strict_types=1);

namespace App\Model\Product\Accessory;

use Doctrine\ORM\Query\ResultSetMapping;
use Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryRepository as BaseProductAccessoryRepository;

/**
 * @property \App\Model\Product\ProductRepository $productRepository
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \App\Model\Product\ProductRepository $productRepository, \Shopsys\FrameworkBundle\Component\Doctrine\QueryBuilderExtender $queryBuilderExtender)
 * @method \App\Model\Product\Product[] getTopOfferedAccessories(\App\Model\Product\Product $product, int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, int $limit)
 * @method \Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessory[] getAllByProduct(\App\Model\Product\Product $product)
 * @method \App\Model\Product\Product[] getAllOfferedAccessoriesByProduct(\App\Model\Product\Product $product, int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method \Doctrine\ORM\QueryBuilder getAllOfferedAccessoriesByProductQueryBuilder(\App\Model\Product\Product $product, int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method \Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessory|null findByProductAndAccessory(\App\Model\Product\Product $product, \App\Model\Product\Product $accessory)
 */
class ProductAccessoryRepository extends BaseProductAccessoryRepository
{
    /**
     * @param int $productId
     * @return int[]
     */
    public function getProductIds(int $productId): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('accessory_product_id', 'productId');
        $accessories = $this->em->createNativeQuery('SELECT accessory_product_id FROM product_accessories WHERE product_id = :productId', $resultSetMapping)
            ->setParameter('productId', $productId)
            ->getResult();

        return array_column($accessories, 'productId');
    }
}
