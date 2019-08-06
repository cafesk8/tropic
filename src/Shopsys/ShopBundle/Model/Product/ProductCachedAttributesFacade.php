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
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterRepository
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
     * @return array
     */
    public function getDistinguishingParametersForProduct(Product $product): array
    {
        $distinguishingParametersForProduct = [
            'firstDistinguishingParameter' => null,
            'secondDistinguishingParameter' => null,
        ];

        $productParameterValues = $this->getProductParameterValues($product);

        $mainVariant = $product->isVariant() ? $product->getMainVariant() : $product;
        $mainVariantGroup = $mainVariant->getMainVariantGroup();

        foreach ($productParameterValues as $productParameterValue) {
            if ($mainVariantGroup !== null && $productParameterValue->getParameter()->getId() === $mainVariantGroup->getDistinguishingParameter()->getId()) {
                $distinguishingParametersForProduct['firstDistinguishingParameter'] = $productParameterValue;
            }

            if ($productParameterValue->getParameter() === $mainVariant->getDistinguishingParameter()) {
                $distinguishingParametersForProduct['secondDistinguishingParameter'] = $productParameterValue;
            }
        }

        return $distinguishingParametersForProduct;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     *
     * @return \Shopsys\ShopBundle\Model\Product\ProductDistinguishingParameterValue
     */
    public function findProductDistinguishingParameterValue(Product $product): ProductDistinguishingParameterValue
    {
        $locale = $this->localization->getLocale();

        $productDistinguishingParameterValue =
            $this->cachedProductDistinguishingParameterValueFacade->findProductDistinguishingParameterValue($product, $locale);

        if ($productDistinguishingParameterValue === null) {
            $productDistinguishingParameterValue = new ProductDistinguishingParameterValue(
                $this->findColorParameterValue($product, $locale),
                $this->findSizeParameterColor($product, $locale)
            );

            $this->cachedProductDistinguishingParameterValueFacade->saveToCache($product, $locale, $productDistinguishingParameterValue);
        }

        return $productDistinguishingParameterValue;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param string $locale
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue|null
     */
    private function findColorParameterValue(Product $product, string $locale): ?ParameterValue
    {
        $colorParameterValue = null;

        /** @var \Shopsys\ShopBundle\Model\Product\Product $mainVariant */
        if ($product->isVariant() === true) {
            $mainVariant = $product->getMainVariant();
        } else {
            $mainVariant = $product;
        }
        $mainVariantGroup = $mainVariant->getMainVariantGroup();

        if ($mainVariantGroup !== null && $mainVariantGroup->getDistinguishingParameter() !== null) {
            $distinguishingParameter = $mainVariantGroup->getDistinguishingParameter();
            $colorParameterValue =
                $this->parameterRepository->findProductParameterValueByProductAndParameterAndLocale(
                    $mainVariant,
                    $distinguishingParameter,
                    $locale
                );
        }

        return $colorParameterValue !== null ? $colorParameterValue->getValue() : null;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param string $locale
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue|null
     */
    private function findSizeParameterColor(Product $product, string $locale): ?ParameterValue
    {
        $sizeParameterValue = null;
        if ($product->getDistinguishingParameter() !== null) {
            $sizeParameterValue = $this->parameterRepository->findProductParameterValueByProductAndParameterAndLocale(
                $product,
                $product->getDistinguishingParameter(),
                $locale
            );
        }

        return $sizeParameterValue !== null ? $sizeParameterValue->getValue() : null;
    }
}
