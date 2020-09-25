<?php

declare(strict_types=1);

namespace App\Model\Product\Pricing;

use App\Model\Pricing\Group\PricingGroup;
use Doctrine\ORM\Query\ResultSetMapping;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceRepository as BaseProductManualInputPriceRepository;
use Shopsys\FrameworkBundle\Model\Product\Product;

/**
 * @method \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPrice[] getByProduct(\App\Model\Product\Product $product)
 * @method \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPrice|null findByProductAndPricingGroup(\App\Model\Product\Product $product, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 */
class ProductManualInputPriceRepository extends BaseProductManualInputPriceRepository
{
    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Pricing\Group\PricingGroup[] $pricingGroups
     * @param int $domainId
     * @param bool $allowSellingDeniedVariants
     * @return array
     */
    public function findByProductAndPricingGroupsForDomain(
        Product $product,
        array $pricingGroups,
        int $domainId,
        bool $allowSellingDeniedVariants = false
    ) {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping
            ->addScalarResult('inputprice', 'inputPrice')
            ->addScalarResult('maxinputprice', 'maxInputPrice')
            ->addScalarResult('pricinggroupid', 'pricingGroupId');
        $parameters = [];
        if ($product->isMainVariant()) {
            $sql = 'SELECT MIN(pmip.input_price) AS inputPrice, MAX(pmip.input_price) AS maxInputPrice, pmip.pricing_group_id as pricingGroupId
                FROM product_manual_input_prices pmip
                INNER JOIN products p ON (pmip.product_id = p.id AND p.main_variant_id = :mainVariantId)
                LEFT JOIN product_visibilities pv ON (p.id = pv.product_id)
                LEFT JOIN product_domains pd ON (pd.product_id = p.id AND pd.domain_id = :domainId)
                WHERE pmip.pricing_group_id IN (:pricingGroupIds)
                AND pmip.input_price > 0
                AND pv.visible = true';

            if (!$allowSellingDeniedVariants) {
                $sql .= ' AND p.calculated_selling_denied = false';
            }
            $parameters['mainVariantId'] = $product->getId();
            $sql .= ' GROUP BY pmip.pricing_group_id';
        } else {
            $sql = 'SELECT pmip.input_price AS inputPrice, 0 AS maxInputPrice, pmip.pricing_group_id AS pricingGroupId
                FROM product_manual_input_prices pmip
                LEFT JOIN product_domains pd ON (pd.product_id = pmip.product_id AND pd.domain_id = :domainId)
                WHERE pmip.pricing_group_id IN (:pricingGroupIds)
                AND pmip.product_id = :productId';
            $parameters['productId'] = $product->getId();
        }
        $parameters['domainId'] = $domainId;
        $parameters['pricingGroupIds'] = array_map(function(PricingGroup $pricingGroup) {
            return $pricingGroup->getId();
        }, $pricingGroups);

        return $this->em->createNativeQuery($sql, $resultSetMapping)->setParameters($parameters)->getResult();
    }
}
