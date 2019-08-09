<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Shopsys\FrameworkBundle\Model\Localization\Localization;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser;
use Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade as BaseProductCachedAttributesFacade;

class ProductCachedAttributesFacade extends BaseProductCachedAttributesFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository
     */
    protected $parameterRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\CachedProductDistinguishingParameterValueFacade
     */
    private $cachedProductDistinguishingParameterValueFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser $productPriceCalculationForUser
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \Shopsys\FrameworkBundle\Model\Localization\Localization $localization
     * @param \Shopsys\ShopBundle\Model\Product\CachedProductDistinguishingParameterValueFacade $cachedProductDistinguishingParameterValueFacade
     */
    public function __construct(
        ProductPriceCalculationForUser $productPriceCalculationForUser,
        ParameterRepository $parameterRepository,
        Localization $localization,
        CachedProductDistinguishingParameterValueFacade $cachedProductDistinguishingParameterValueFacade
    ) {
        parent::__construct($productPriceCalculationForUser, $parameterRepository, $localization);
        $this->cachedProductDistinguishingParameterValueFacade = $cachedProductDistinguishingParameterValueFacade;
    }

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

            if (array_key_exists($mainVariantId, $finalResult) && is_array($finalResult[$mainVariantId])) {
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

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param int $parameterId
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue|null
     */
    public function getProductParameterValueByParameterId(Product $product, int $parameterId): ?ProductParameterValue
    {
        $productParametersValue = $this->getProductParameterValues($product);

        /** @var \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue $productParameterValue */
        foreach ($productParametersValue as $productParameterValue) {
            if ($productParameterValue->getParameter()->getId() === $parameterId) {
                return $productParameterValue;
            }
        }

        return null;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\ShopBundle\Model\Product\ProductDistinguishingParameterValue
     */
    public function getProductDistinguishingParameterValue(Product $product): ProductDistinguishingParameterValue
    {
        $locale = $this->localization->getLocale();

        $productDistinguishingParameterValue =
            $this->cachedProductDistinguishingParameterValueFacade->findProductDistinguishingParameterValue($product, $locale);

        if ($productDistinguishingParameterValue === null) {
            $productDistinguishingParameterValue = $this->createProductDistinguishingParameterValue($product);
            $this->cachedProductDistinguishingParameterValueFacade->saveToCache($product, $locale, $productDistinguishingParameterValue);
        }

        return $productDistinguishingParameterValue;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\ShopBundle\Model\Product\ProductDistinguishingParameterValue
     */
    private function createProductDistinguishingParameterValue(Product $product): ProductDistinguishingParameterValue
    {
        $productParameterValues = $this->getProductParameterValues($product);

        $mainVariant = $product->isVariant() ? $product->getMainVariant() : $product;
        $mainVariantGroup = $mainVariant->getMainVariantGroup();

        $firstDistinguishingParameterValue = null;
        $secondDistinguishingParameterValue = null;
        $productDistinguishingParameterValue = null;
        foreach ($productParameterValues as $productParameterValue) {
            if ($mainVariantGroup !== null && $productParameterValue->getParameter()->getId() === $mainVariantGroup->getDistinguishingParameter()->getId()) {
                $firstDistinguishingParameterValue = $productParameterValue;
            }
            if ($productParameterValue->getParameter() === $mainVariant->getDistinguishingParameter()) {
                $secondDistinguishingParameterValue = $productParameterValue;
            }
        }

        $productDistinguishingParameterValue = new ProductDistinguishingParameterValue(
            $firstDistinguishingParameterValue,
            $secondDistinguishingParameterValue
        );

        return $productDistinguishingParameterValue;
    }
}
