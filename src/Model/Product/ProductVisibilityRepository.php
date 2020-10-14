<?php

declare(strict_types=1);

namespace App\Model\Product;

use DateTime;
use Doctrine\ORM\Query\ResultSetMapping;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityRepository as BaseProductVisibilityRepository;

/**
 * @property \App\Model\Pricing\Group\PricingGroupRepository $pricingGroupRepository
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \Shopsys\FrameworkBundle\Component\Domain\Domain $domain, \App\Model\Pricing\Group\PricingGroupRepository $pricingGroupRepository)
 * @method markProductsForRecalculationAffectedByCategory(\App\Model\Category\Category $category)
 * @method createAndRefreshProductVisibilitiesForPricingGroup(\App\Model\Pricing\Group\PricingGroup $pricingGroup, int $domainId)
 * @method \Shopsys\FrameworkBundle\Model\Product\ProductVisibility getProductVisibility(\App\Model\Product\Product $product, \App\Model\Pricing\Group\PricingGroup $pricingGroup, int $domainId)
 * @method \Shopsys\FrameworkBundle\Model\Product\ProductVisibility[] findProductVisibilitiesByDomainIdAndProduct(int $domainId, \App\Model\Product\Product $product)
 */
class ProductVisibilityRepository extends BaseProductVisibilityRepository
{
    /**
     * Now uses ProductDomain::shown instead of Product::calculatedHidden
     *
     * @param bool $onlyMarkedProducts
     */
    protected function refreshGlobalProductVisibility($onlyMarkedProducts)
    {
        if ($onlyMarkedProducts) {
            $onlyMarkedProductsWhereClause = ' WHERE p.recalculate_visibility = TRUE';
        } else {
            $onlyMarkedProductsWhereClause = '';
        }

        $query = $this->em->createNativeQuery(
            'UPDATE products AS p
            SET calculated_visibility = EXISTS (
                SELECT 1
                FROM product_domains pd
                WHERE pd.product_id = p.id
                    AND pd.shown = TRUE
            ) AND EXISTS(
                SELECT 1
                FROM product_visibilities AS pv
                WHERE pv.product_id = p.id
                    AND pv.visible = TRUE
            )
            ' . $onlyMarkedProductsWhereClause,
            new ResultSetMapping()
        );
        $query->execute();
    }

    /**
     * Product group is hidden when any of its products are missing prices for ordinary customer or registered customer
     * If any product in product group doesn't have a name for selected domain then whole product group is hidden
     * If any of the products in the set is hidden on the given domain, then the whole set is hidden
     * Now uses ProductDomain::shown instead of Product::calculatedHidden
     *
     * @param bool $onlyMarkedProducts
     */
    protected function calculateIndependentVisibility($onlyMarkedProducts)
    {
        $now = new DateTime();
        if ($onlyMarkedProducts) {
            $onlyMarkedProductsCondition = ' AND p.recalculate_visibility = TRUE';
        } else {
            $onlyMarkedProductsCondition = '';
        }

        $query = $this->em->createNativeQuery(
            'UPDATE product_visibilities AS pv
            SET visible = CASE
                    WHEN (
                        p.selling_denied = FALSE
                        AND
                        pd.shown = TRUE
                        AND
                        (p.selling_from IS NULL OR p.selling_from <= :now)
                        AND
                        (p.selling_to IS NULL OR p.selling_to >= :now)
                        AND
                        (
                            p.variant_type = :variantTypeMain
                            OR
                            EXISTS (
                                SELECT 1
                                FROM product_manual_input_prices as pmip
                                WHERE pmip.input_price IS NOT NULL AND pmip.input_price > 0
                                    AND pmip.product_id = pv.product_id
                                    AND pmip.pricing_group_id = pv.pricing_group_id
                            )
                        )
                        AND
                        (
                            p.pohoda_product_type != :groupProductType
                            OR
                            (
                                (
                                    SELECT COUNT(ps.item_id)
                                    FROM product_sets AS ps
                                    JOIN product_manual_input_prices AS pmip ON pmip.product_id = ps.item_id
                                    JOIN pricing_groups AS pcg ON pcg.id = pmip.pricing_group_id
                                    WHERE pmip.input_price IS NOT NULL AND pmip.input_price > 0
                                        AND ps.main_product_id = pv.product_id
                                        AND pcg.internal_id IN (\'ordinary_customer\', \'registered_customer\')
                                        AND pcg.domain_id = pv.domain_id
                                )
                                =
                                (
                                    SELECT COUNT(pg2.item_id) * 2
                                    FROM product_sets AS pg2
                                    WHERE pg2.main_product_id = pv.product_id
                                )
                                AND
                                NOT EXISTS (
                                    SELECT 1
                                    FROM product_sets AS ps
                                    JOIN product_translations AS pt2 ON pt2.translatable_id = ps.item_id
                                    WHERE ps.main_product_id = pv.product_id
                                        AND pt2.locale = :locale
                                        AND pt2.name IS NULL
                                )
                            )
                        )
                        AND EXISTS (
                            SELECT 1
                            FROM product_translations AS pt
                            WHERE pt.translatable_id = pv.product_id
                                AND pt.locale = :locale
                                AND pt.name IS NOT NULL
                        )
                        AND EXISTS (
                            SELECT 1
                            FROM product_category_domains AS pcd
                            JOIN category_domains AS cd ON cd.category_id = pcd.category_id
                                AND cd.domain_id = pcd.domain_id
                            WHERE pcd.product_id = p.id
                                AND pcd.domain_id = pv.domain_id
                                AND cd.visible = TRUE
                        )
                        AND TRUE = ALL(
                          SELECT pd.shown
                          FROM product_sets AS ps
                          JOIN product_domains AS pd ON pd.product_id = ps.item_id
                          WHERE ps.main_product_id = pv.product_id
                          AND pd.domain_id = pv.domain_id
                        )
                    )
                    THEN TRUE
                    ELSE FALSE
                END
            FROM products AS p
            JOIN product_domains AS pd ON pd.product_id = p.id
            WHERE p.id = pv.product_id
                AND pv.domain_id = :domainId
                AND pv.domain_id = pd.domain_id
                AND pv.pricing_group_id = :pricingGroupId
            ' . $onlyMarkedProductsCondition,
            new ResultSetMapping()
        );

        foreach ($this->pricingGroupRepository->getAll() as $pricingGroup) {
            $domain = $this->domain->getDomainConfigById($pricingGroup->getDomainId());
            $query->execute([
                'now' => $now,
                'locale' => $domain->getLocale(),
                'domainId' => $domain->getId(),
                'pricingGroupId' => $pricingGroup->getId(),
                'variantTypeMain' => Product::VARIANT_TYPE_MAIN,
                'groupProductType' => Product::POHODA_PRODUCT_TYPE_ID_PRODUCT_SET,
            ]);
        }
    }
}
