<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use DateTime;
use Shopsys\FrameworkBundle\Model\Localization\Localization;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser;
use Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade as BaseProductCachedAttributesFacade;
use Shopsys\ShopBundle\Model\Transport\DeliveryDate\DeliveryDateFacade;
use Shopsys\ShopBundle\Model\Transport\Transport;

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
     * @var \Shopsys\ShopBundle\Model\Transport\DeliveryDate\DeliveryDateFacade
     */
    private $deliveryDateFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser $productPriceCalculationForUser
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \Shopsys\FrameworkBundle\Model\Localization\Localization $localization
     * @param \Shopsys\ShopBundle\Model\Product\CachedProductDistinguishingParameterValueFacade $cachedProductDistinguishingParameterValueFacade
     * @param \Shopsys\ShopBundle\Model\Transport\DeliveryDate\DeliveryDateFacade $deliveryDateFacade
     */
    public function __construct(
        ProductPriceCalculationForUser $productPriceCalculationForUser,
        ParameterRepository $parameterRepository,
        Localization $localization,
        CachedProductDistinguishingParameterValueFacade $cachedProductDistinguishingParameterValueFacade,
        DeliveryDateFacade $deliveryDateFacade
    ) {
        parent::__construct($productPriceCalculationForUser, $parameterRepository, $localization);
        $this->cachedProductDistinguishingParameterValueFacade = $cachedProductDistinguishingParameterValueFacade;
        $this->deliveryDateFacade = $deliveryDateFacade;
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

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\Transport|null $transport
     * @return \DateTime
     */
    public function getExpectedDeliveryDate(?Transport $transport = null): DateTime
    {
        return $this->deliveryDateFacade->getExpectedDeliveryDate($transport);
    }
}
