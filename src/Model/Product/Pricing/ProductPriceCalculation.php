<?php

declare(strict_types=1);

namespace App\Model\Product\Pricing;

use App\Model\Pricing\Currency\Currency;
use App\Model\Pricing\Vat\Vat;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Model\Pricing\BasePriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Pricing\PriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\PricingSetting;
use Shopsys\FrameworkBundle\Model\Pricing\Rounding;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceRepository;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculation as BaseProductPriceCalculation;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository;

/**
 * @property \App\Model\Product\Pricing\ProductManualInputPriceRepository $productManualInputPriceRepository
 * @property \App\Model\Product\ProductRepository $productRepository
 * @property \App\Model\Pricing\Currency\CurrencyFacade|null $currencyFacade
 * @method setCurrencyFacade(\App\Model\Pricing\Currency\CurrencyFacade $currencyFacade)
 * @method \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice calculateMainVariantPrice(\App\Model\Product\Product $mainVariant, int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 */
class ProductPriceCalculation extends BaseProductPriceCalculation
{
    /**
     * @var \App\Component\Setting\Setting
     */
    private $setting;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\PriceCalculation
     */
    private $priceCalculation;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Rounding
     */
    private $rounding;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\BasePriceCalculation $basePriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Pricing\PricingSetting $pricingSetting
     * @param \App\Model\Product\Pricing\ProductManualInputPriceRepository $productManualInputPriceRepository
     * @param \App\Model\Product\ProductRepository $productRepository
     * @param \App\Component\Setting\Setting $setting
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \Shopsys\FrameworkBundle\Model\Pricing\PriceCalculation $priceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Rounding $rounding
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     */
    public function __construct(
        BasePriceCalculation $basePriceCalculation,
        PricingSetting $pricingSetting,
        ProductManualInputPriceRepository $productManualInputPriceRepository,
        ProductRepository $productRepository,
        Setting $setting,
        PricingGroupFacade $pricingGroupFacade,
        PriceCalculation $priceCalculation,
        Rounding $rounding,
        CurrencyFacade $currencyFacade
    ) {
        parent::__construct($basePriceCalculation, $pricingSetting, $productManualInputPriceRepository, $productRepository, $currencyFacade);
        $this->setting = $setting;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->priceCalculation = $priceCalculation;
        $this->rounding = $rounding;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Pricing\ProductPrice
     */
    public function calculatePrice(Product $product, $domainId, PricingGroup $pricingGroup)
    {
        return $this->calculateProductPriceForPricingGroup($product, $pricingGroup);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Pricing\ProductPrice
     */
    protected function calculateProductPriceForPricingGroup(Product $product, PricingGroup $pricingGroup)
    {
        $domainId = $pricingGroup->getDomainId();
        $defaultPricingGroup = $this->pricingGroupFacade->getById(
            $this->setting->getForDomain(Setting::DEFAULT_PRICING_GROUP, $domainId)
        );

        $manualInputPrices = $this->productManualInputPriceRepository->findByProductAndPricingGroupsForDomain($product, [
            $pricingGroup,
            $defaultPricingGroup,
        ], $domainId);

        $inputPrice = Money::zero();
        $defaultPrice = Money::zero();
        $defaultMaxInputPrice = Money::zero();
        $maxInputPrice = Money::zero();

        foreach ($manualInputPrices as $manualInputPrice) {
            if ($manualInputPrice !== null && $manualInputPrice['pricingGroupId'] === $defaultPricingGroup->getId() && $manualInputPrice['inputPrice'] !== null) {
                $defaultPrice = Money::create($manualInputPrice['inputPrice']);
                $defaultMaxInputPrice = Money::create($manualInputPrice['maxInputPrice']);
                break;
            }
        }

        foreach ($manualInputPrices as $manualInputPrice) {
            if ($manualInputPrice !== null) {
                if ($manualInputPrice['pricingGroupId'] === $pricingGroup->getId()) {
                    if (!$pricingGroup->isCalculatedFromDefault()) {
                        $inputPrice = $manualInputPrice['inputPrice'] ? Money::create($manualInputPrice['inputPrice']) : Money::zero();
                        $maxInputPrice = $inputPrice;
                    } elseif ($product->isRegistrationDiscountDisabled()) {
                        $inputPrice = $defaultPrice;
                    } elseif ($manualInputPrice['inputPrice'] !== null) {
                        $pricingGroupCoefficient = strval($pricingGroup->getDiscountCoefficient());
                        $inputPrice = $defaultPrice->multiply($pricingGroupCoefficient);
                        $maxInputPrice = $defaultMaxInputPrice->multiply($pricingGroupCoefficient);
                    }
                }
            }
        }

        $isPriceFrom = false;
        if ($product->isMainVariant() && $maxInputPrice->isGreaterThan($inputPrice)) {
            $isPriceFrom = true;
        }

        $defaultCurrency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainId);

        $defaultPrice = $this->calculateBasePriceRoundedByCurrency(
            $defaultPrice,
            $this->pricingSetting->getInputPriceType(),
            $product->getVatForDomain($domainId),
            $defaultCurrency
        );

        if ($product->isGiftCertificate()) {
            $basePrice = $defaultPrice;
        } else {
            $basePrice = $this->calculateBasePriceRoundedByCurrency(
                $inputPrice,
                $this->pricingSetting->getInputPriceType(),
                $product->getVatForDomain($domainId),
                $defaultCurrency
            );
        }

        return new ProductPrice(
            $basePrice,
            $isPriceFrom,
            $product->getId(),
            $defaultPrice
        );
    }

    /**
     * copy-pasted from BasePriceCalculation where the method is deprecated since 8.1
     *
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $inputPrice
     * @param int $inputPriceType
     * @param \App\Model\Pricing\Vat\Vat $vat
     * @param \App\Model\Pricing\Currency\Currency $currency
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    public function calculateBasePriceRoundedByCurrency(
        Money $inputPrice,
        int $inputPriceType,
        Vat $vat,
        Currency $currency
    ): Price {
        $basePriceWithVat = $this->getBasePriceWithVatRoundedByCurrency($inputPrice, $inputPriceType, $vat, $currency);
        $vatAmount = $this->priceCalculation->getVatAmountByPriceWithVat($basePriceWithVat, $vat);
        $basePriceWithoutVat = $this->rounding->roundPriceWithoutVat($basePriceWithVat->subtract($vatAmount));

        return new Price($basePriceWithoutVat, $basePriceWithVat);
    }

    /**
     * copy-pasted from BasePriceCalculation where the method is deprecated since 8.1
     *
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $inputPrice
     * @param int $inputPriceType
     * @param \App\Model\Pricing\Vat\Vat $vat
     * @param \App\Model\Pricing\Currency\Currency $currency
     * @return \Shopsys\FrameworkBundle\Component\Money\Money
     */
    protected function getBasePriceWithVatRoundedByCurrency(
        Money $inputPrice,
        int $inputPriceType,
        Vat $vat,
        Currency $currency
    ): Money {
        switch ($inputPriceType) {
            case PricingSetting::INPUT_PRICE_TYPE_WITH_VAT:
                return $this->rounding->roundPriceWithVatByCurrency($inputPrice, $currency);

            case PricingSetting::INPUT_PRICE_TYPE_WITHOUT_VAT:
                return $this->rounding->roundPriceWithVatByCurrency(
                    $this->priceCalculation->applyVatPercent($inputPrice, $vat),
                    $currency
                );

            default:
                throw new \Shopsys\FrameworkBundle\Model\Pricing\Exception\InvalidInputPriceTypeException();
        }
    }
}
