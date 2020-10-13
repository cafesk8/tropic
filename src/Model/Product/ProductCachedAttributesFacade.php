<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Product\Pricing\ProductPrice;
use App\Model\Product\Pricing\ProductPriceCalculation;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Localization\Localization;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForCustomerUser;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade as BaseProductCachedAttributesFacade;

/**
 * @property \App\Model\Product\Pricing\ProductPriceCalculationForCustomerUser $productPriceCalculationForCustomerUser
 * @property \App\Model\Product\Parameter\ProductParameterValue[][] $parameterValuesByProductId
 */
class ProductCachedAttributesFacade extends BaseProductCachedAttributesFacade
{
    /**
     * @var \App\Model\Product\Parameter\ParameterRepository
     */
    protected $parameterRepository;

    /**
     * @var \App\Model\Product\Pricing\ProductPriceCalculation
     */
    private $productPriceCalculation;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser
     */
    private $currentCustomerUser;

    /**
     * @var \App\Model\Product\Pricing\ProductPrice[]
     */
    protected $registeredCustomerPricesByProductId;

    /**
     * @var \App\Model\Product\Pricing\ProductPrice[]
     */
    private array $salePricesByProductId = [];

    /**
     * @param \App\Model\Product\Pricing\ProductPriceCalculationForCustomerUser $productPriceCalculationForUser
     * @param \App\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \Shopsys\FrameworkBundle\Model\Localization\Localization $localization
     * @param \App\Model\Product\Pricing\ProductPriceCalculation $productPriceCalculation
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     */
    public function __construct(
        ProductPriceCalculationForCustomerUser $productPriceCalculationForUser,
        ParameterRepository $parameterRepository,
        Localization $localization,
        ProductPriceCalculation $productPriceCalculation,
        Domain $domain,
        PricingGroupFacade $pricingGroupFacade,
        CurrentCustomerUser $currentCustomerUser
    ) {
        parent::__construct($productPriceCalculationForUser, $parameterRepository, $localization);
        $this->productPriceCalculation = $productPriceCalculation;
        $this->domain = $domain;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->currentCustomerUser = $currentCustomerUser;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\Pricing\ProductPrice|null
     */
    public function getProductRegisteredCustomerPrice(Product $product): ?ProductPrice
    {
        if (isset($this->registeredCustomerPricesByProductId[$product->getId()])) {
            return $this->registeredCustomerPricesByProductId[$product->getId()];
        }

        $registeredCustomerPricingGroup = $this->pricingGroupFacade->getRegisteredCustomerPricingGroup($this->domain->getId());

        /** @var \App\Model\Customer\User\CustomerUser $customerUser */
        $customerUser = $this->currentCustomerUser->findCurrentCustomerUser();

        if ($customerUser !== null && $customerUser->getPricingGroup()->isRegisteredCustomerPricingGroup()) {
            return null;
        }

        try {
            $registeredCustomerProductPrice = $this->productPriceCalculation->calculatePrice($product, $this->domain->getId(), $registeredCustomerPricingGroup);
        } catch (\Shopsys\FrameworkBundle\Model\Product\Pricing\Exception\MainVariantPriceCalculationException $ex) {
            $registeredCustomerProductPrice = null;
        }

        /** @var \App\Model\Product\Pricing\ProductPrice $productSellingPrice */
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
     * @param \App\Model\Product\Product $product
     * @param string|null $locale
     * @return \App\Model\Product\Parameter\ProductParameterValue[]
     */
    public function getProductParameterValues(BaseProduct $product, ?string $locale = null)
    {
        if (isset($this->parameterValuesByProductId[$product->getId()])) {
            return $this->parameterValuesByProductId[$product->getId()];
        }

        if ($locale === null) {
            $locale = $this->localization->getLocale();
        }

        $productParameterValues = $this->parameterRepository->getProductParameterValuesByProductSortedByPosition($product, $locale);
        foreach ($productParameterValues as $index => $productParameterValue) {
            $parameter = $productParameterValue->getParameter();
            if (!$parameter->isVisibleOnFrontend() || $parameter->getName($locale) === null
                || $productParameterValue->getValue()->getLocale() !== $locale
            ) {
                unset($productParameterValues[$index]);
            }
        }
        $this->parameterValuesByProductId[$product->getId()] = $productParameterValues;

        return $productParameterValues;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param bool|null $isSale
     * @return \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice|null
     */
    public function getProductSellingPrice(BaseProduct $product, ?bool $isSale = null)
    {
        if ($isSale === true && isset($this->salePricesByProductId[$product->getId()])) {
            return $this->salePricesByProductId[$product->getId()];
        } elseif ($isSale !== true && isset($this->sellingPricesByProductId[$product->getId()])) {
            return $this->sellingPricesByProductId[$product->getId()];
        }

        return $this->calculateProductSellingPriceAndSaveToCache($product, $isSale);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param bool|null $isSale
     * @return \App\Model\Product\Pricing\ProductPrice
     */
    public function calculateProductSellingPriceAndSaveToCache(Product $product, ?bool $isSale = null): ProductPrice
    {
        $productPrice = $this->productPriceCalculationForCustomerUser->calculatePriceForCurrentUser(
            $product,
            $isSale ?? $product->isInAnySaleStock()
        );
        if ($isSale === true) {
            $this->salePricesByProductId[$product->getId()] = $productPrice;
        } else {
            $this->sellingPricesByProductId[$product->getId()] = $productPrice;
        }

        return $productPrice;
    }
}
