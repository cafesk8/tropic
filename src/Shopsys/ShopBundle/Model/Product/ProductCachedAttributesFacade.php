<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue;
use Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade as BaseProductCachedAttributesFacade;

class ProductCachedAttributesFacade extends BaseProductCachedAttributesFacade
{
    /**
     * This method returns for every main variant all values of distinguishing parameter used in variants with variantId if variant has that value
     *
     * Example result:
     *
     * [
     *     'Main variant ID 1' => [
     *          'Distinguishing parameter value "Blue"' => null // null is here, because there is no variant for main variant with ID 1
     *          'Distinguishing parameter value "Green"' => 12 // Here is ID of variant that has value Green for distinguishing parameter
     *     ],
     *     'Main variant ID 2' => [
     *          'Distinguishing parameter value "Blue"' => null
     *          'Distinguishing parameter value "Green"' => null
     *     ],
     *     'Main variant ID 3' => [
     *          'Distinguishing parameter value "Blue"' => 13
     *          'Distinguishing parameter value "Green"' => 43
     *     ],
     * ]
     *
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $allVariants
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue|null
     */
    public function findDistinguishingParameterValuesForProducts(array $allVariants): array
    {
        $distinguishingParameterValues = [];
        $parameterValuesWithProductIds = [];
        $productWithVariantIds = [];
        foreach ($allVariants as $variant) {
            $productParameterValues = $this->getProductParameterValues($variant);

            $productWithVariantIds[$variant->getMainVariant()->getId()][] = $variant->getId();

            foreach ($productParameterValues as $productParameterValue) {
                /** @var \Shopsys\ShopBundle\Model\Product\Product $mainVariant */
                $mainVariant = $variant->getMainVariant();

                if ($productParameterValue->getParameter() === $mainVariant->getDistinguishingParameter()) {
                    $parameterValuesWithProductIds[$productParameterValue->getValue()->getText()][] = $variant->getId();

                    if (in_array($productParameterValue->getValue(), $distinguishingParameterValues, true) === false) {
                        $distinguishingParameterValues[] = $productParameterValue->getValue()->getText();
                    }
                }
            }
        }

        $finalResult = [];

        foreach ($productWithVariantIds as $mainVariantId => $variantIds) {
            foreach ($distinguishingParameterValues as $distinguishingParameterValue) {
                if (array_key_exists($distinguishingParameterValue, $parameterValuesWithProductIds) === true) {
                    $productId = array_intersect($parameterValuesWithProductIds[$distinguishingParameterValue], $variantIds);
                    $finalResult[$mainVariantId][$distinguishingParameterValue] = array_shift($productId);
                } else {
                    $finalResult[$mainVariantId][$distinguishingParameterValue] = null;
                }
            }

            if (is_array($finalResult[$mainVariantId])) {
                ksort($finalResult[$mainVariantId], SORT_NATURAL);
            }
        }

        return $finalResult;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue|null
     */
    public function findMainVariantGroupDistinguishingParameterValue(Product $product): ?ParameterValue
    {
        $productParameterValues = $this->getProductParameterValues($product);

        foreach ($productParameterValues as $productParameterValue) {
            if ($productParameterValue->getParameter() === $product->getMainVariantGroup()->getDistinguishingParameter()) {
                return $productParameterValue->getValue();
            }
        }

        return null;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue|null
     */
    public function findDistinguishingProductParameterValueForVariant(Product $product): ?ProductParameterValue
    {
        if ($product->isVariant() === false) {
            return null;
        }

        /** @var \Shopsys\ShopBundle\Model\Product\Product $mainVariant */
        $mainVariant = $product->getMainVariant();

        if ($mainVariant->getDistinguishingParameter() === null) {
            return null;
        }

        $productParameterValues = $this->getProductParameterValues($product);

        foreach ($productParameterValues as $productParameterValue) {
            if ($productParameterValue->getParameter() === $mainVariant->getDistinguishingParameter()) {
                return $productParameterValue;
            }
        }

        return null;
    }
}
