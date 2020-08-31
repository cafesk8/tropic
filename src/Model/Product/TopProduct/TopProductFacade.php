<?php

declare(strict_types=1);

namespace App\Model\Product\TopProduct;

use Shopsys\FrameworkBundle\Model\Product\TopProduct\TopProductFacade as BaseTopProductFacade;

/**
 * @property \App\Model\Product\TopProduct\TopProductRepository $topProductRepository
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \App\Model\Product\TopProduct\TopProductRepository $topProductRepository, \Shopsys\FrameworkBundle\Model\Product\TopProduct\TopProductFactoryInterface $topProductFactory)
 * @method \App\Model\Product\Product[] getAllOfferedProducts(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method saveTopProductsForDomain(int $domainId, \App\Model\Product\Product[] $products)
 */
class TopProductFacade extends BaseTopProductFacade
{
    /**
     * @param int $domainId
     * @return int[]
     */
    public function getProductPositionIndexedById(int $domainId): array
    {
        $idsIndexedByPosition = [];

        foreach ($this->topProductRepository->getProductIdsAndPosition($domainId) as $topProductArray) {
            $idsIndexedByPosition[$topProductArray['productId']] = $topProductArray['position'];
        }

        return $idsIndexedByPosition;
    }
}
