<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use DateTime;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
use Shopsys\FrameworkBundle\Model\Localization\Localization;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade as BaseProductCachedAttributesFacade;
use Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade;
use Shopsys\ShopBundle\Model\Product\Pricing\ProductPriceCalculation;
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
     * @var \Shopsys\ShopBundle\Model\Product\Pricing\ProductPriceCalculation
     */
    private $productPriceCalculation;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer
     */
    private $currentCustomer;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice[]
     */
    protected $registeredCustomerPricesByProductId;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser $productPriceCalculationForUser
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \Shopsys\FrameworkBundle\Model\Localization\Localization $localization
     * @param \Shopsys\ShopBundle\Model\Product\CachedProductDistinguishingParameterValueFacade $cachedProductDistinguishingParameterValueFacade
     * @param \Shopsys\ShopBundle\Model\Transport\DeliveryDate\DeliveryDateFacade $deliveryDateFacade
     * @param \Shopsys\ShopBundle\Model\Product\Pricing\ProductPriceCalculation $productPriceCalculation
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     */
    public function __construct(
        ProductPriceCalculationForUser $productPriceCalculationForUser,
        ParameterRepository $parameterRepository,
        Localization $localization,
        CachedProductDistinguishingParameterValueFacade $cachedProductDistinguishingParameterValueFacade,
        DeliveryDateFacade $deliveryDateFacade,
        ProductPriceCalculation $productPriceCalculation,
        Domain $domain,
        PricingGroupFacade $pricingGroupFacade,
        CurrentCustomer $currentCustomer
    ) {
        parent::__construct($productPriceCalculationForUser, $parameterRepository, $localization);
        $this->cachedProductDistinguishingParameterValueFacade = $cachedProductDistinguishingParameterValueFacade;
        $this->deliveryDateFacade = $deliveryDateFacade;
        $this->productPriceCalculation = $productPriceCalculation;
        $this->domain = $domain;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->currentCustomer = $currentCustomer;
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
     * @param string|null $locale
     * @return array
     */
    public function findDistinguishingParameterValuesForProducts(array $allVariants, ?string $locale = null): array
    {
        $distinguishingParameterValues = [];
        $parameterValuesWithProductIds = [];
        $productWithVariantIds = [];
        foreach ($allVariants as $variant) {
            $distinguishingParameterValue = $this->getProductDistinguishingParameterValue($variant, $locale);
            $secondDistinguishingParameterValue = $distinguishingParameterValue->getSecondDistinguishingParameterValue();

            $productWithVariantIds[$variant->getMainVariant()->getId()][] = $variant->getId();

            if ($secondDistinguishingParameterValue === null) {
                continue;
            }

            $parameterValuesWithProductIds[$secondDistinguishingParameterValue][] = $variant->getId();

            if (in_array($secondDistinguishingParameterValue, $distinguishingParameterValues, true) === false) {
                $distinguishingParameterValues[] = $secondDistinguishingParameterValue;
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
                uksort($finalResult[$mainVariantId], [SizeHelper::class, 'compareSizes']);
            }
        }

        return $finalResult;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param string|null $locale
     * @return \Shopsys\ShopBundle\Model\Product\ProductDistinguishingParameterValue
     */
    public function getProductDistinguishingParameterValue(Product $product, ?string $locale = null): ProductDistinguishingParameterValue
    {
        if ($locale === null) {
            $locale = $this->localization->getLocale();
        }

        $productDistinguishingParameterValue =
            $this->cachedProductDistinguishingParameterValueFacade->findProductDistinguishingParameterValue($product, $locale);

        if ($productDistinguishingParameterValue === null) {
            $productDistinguishingParameterValue = $this->createProductDistinguishingParameterValue($product, $locale);
            $this->cachedProductDistinguishingParameterValueFacade->saveToCache($product, $locale, $productDistinguishingParameterValue);
        }

        return $productDistinguishingParameterValue;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param string $locale
     * @return \Shopsys\ShopBundle\Model\Product\ProductDistinguishingParameterValue
     */
    private function createProductDistinguishingParameterValue(Product $product, string $locale): ProductDistinguishingParameterValue
    {
        $productParameterValues = $this->getProductParameterValues($product, $locale);

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
            $secondDistinguishingParameterValue,
            $locale
        );

        return $productDistinguishingParameterValue;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Shopsys\ShopBundle\Model\Transport\Transport|null $transport
     * @return \DateTime
     */
    public function getExpectedDeliveryDate(Product $product, ?Transport $transport = null): DateTime
    {
        return $this->deliveryDateFacade->getExpectedDeliveryDate($product, $transport);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @return \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice|null
     */
    public function getProductRegisteredCustomerPrice(Product $product): ?ProductPrice
    {
        if (isset($this->registeredCustomerPricesByProductId[$product->getId()])) {
            return $this->registeredCustomerPricesByProductId[$product->getId()];
        }

        $registeredCustomerPricingGroup = $this->pricingGroupFacade->getByNameAndDomainId(PricingGroup::PRICING_GROUP_REGISTERED_CUSTOMER, $this->domain->getId());

        /** @var \Shopsys\ShopBundle\Model\Customer\User $user */
        $user = $this->currentCustomer->findCurrentUser();

        if ($registeredCustomerPricingGroup === null || ($user !== null && $user->getPricingGroup()->getId() === $registeredCustomerPricingGroup->getId())) {
            return null;
        }

        try {
            $registeredCustomerProductPrice = $this->productPriceCalculation->calculatePrice($product, $this->domain->getId(), $registeredCustomerPricingGroup);
        } catch (\Shopsys\FrameworkBundle\Model\Product\Pricing\Exception\MainVariantPriceCalculationException $ex) {
            $registeredCustomerProductPrice = null;
        }

        /** @var \Shopsys\ShopBundle\Model\Product\Pricing\ProductPrice $productSellingPrice */
        $productSellingPrice = $this->getProductSellingPrice($product);

        if ($registeredCustomerProductPrice->getPriceWithVat()->isLessThan($productSellingPrice->getPriceWithVat())
            && $registeredCustomerProductPrice->getPriceWithVat()->isLessThan($productSellingPrice->defaultProductPrice()->getPriceWithVat())
        ) {
            $this->registeredCustomerPricesByProductId[$product->getId()] = $registeredCustomerProductPrice;

            return $registeredCustomerProductPrice;
        }

        return null;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param string|null $locale
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue[]
     */
    public function getProductParameterValues(Product $product, ?string $locale = null)
    {
        if (isset($this->parameterValuesByProductId[$product->getId()])) {
            return $this->parameterValuesByProductId[$product->getId()];
        }

        if ($locale === null) {
            $locale = $this->localization->getLocale();
        }

        $productParameterValues = $this->parameterRepository->getProductParameterValuesByProductSortedByName($product, $locale);
        foreach ($productParameterValues as $index => $productParameterValue) {
            $parameter = $productParameterValue->getParameter();
            if ($parameter->getName($locale) === null
                || $productParameterValue->getValue()->getLocale() !== $locale
            ) {
                unset($productParameterValues[$index]);
            }
        }
        $this->parameterValuesByProductId[$product->getId()] = $productParameterValues;

        return $productParameterValues;
    }
}
