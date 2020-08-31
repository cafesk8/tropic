<?php

declare(strict_types=1);

namespace App\Model\Product\TopProduct;

use Doctrine\ORM\Query\ResultSetMapping;
use Shopsys\FrameworkBundle\Model\Product\TopProduct\TopProductRepository as BaseTopProductRepository;

/**
 * @property \App\Model\Product\ProductRepository $productRepository
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $entityManager, \App\Model\Product\ProductRepository $productRepository)
 * @method \App\Model\Product\Product[] getOfferedProductsForTopProductsOnDomain(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 */
class TopProductRepository extends BaseTopProductRepository
{
    /**
     * @param int $domainId
     * @return array[]
     */
    public function getProductIdsAndPosition(int $domainId): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('product_id', 'productId');
        $resultSetMapping->addScalarResult('position', 'position');

        return $this->em->createNativeQuery('SELECT product_id, position FROM products_top WHERE domain_id = :domainId', $resultSetMapping)
            ->setParameter('domainId', $domainId)
            ->getResult();
    }
}
