<?php

declare(strict_types=1);

namespace App\Model\Product\Pricing;

use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Model\Pricing\BasePriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade;
use Shopsys\FrameworkBundle\Model\Pricing\PricingSetting;
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
     * @param \Shopsys\FrameworkBundle\Model\Pricing\BasePriceCalculation $basePriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Pricing\PricingSetting $pricingSetting
     * @param \App\Model\Product\Pricing\ProductManualInputPriceRepository $productManualInputPriceRepository
     * @param \App\Model\Product\ProductRepository $productRepository
     * @param \App\Component\Setting\Setting $setting
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     */
    public function __construct(
        BasePriceCalculation $basePriceCalculation,
        PricingSetting $pricingSetting,
        ProductManualInputPriceRepository $productManualInputPriceRepository,
        ProductRepository $productRepository,
        Setting $setting,
        PricingGroupFacade $pricingGroupFacade,
        CurrencyFacade $currencyFacade
    ) {
        parent::__construct($basePriceCalculation, $pricingSetting, $productManualInputPriceRepository, $productRepository, $currencyFacade);
        $this->setting = $setting;
        $this->pricingGroupFacade = $pricingGroupFacade;
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
        $productActionPrice = Money::zero();
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
                $productActionPrice = $manualInputPrice['actionPrice'] ? Money::create($manualInputPrice['actionPrice']) : Money::zero();

                if ($manualInputPrice['pricingGroupId'] === $pricingGroup->getId() && $manualInputPrice['inputPrice'] !== null) {
                    $pricingGroupCoefficient = strval($pricingGroup->getDiscountCoefficient());
                    $inputPrice = $defaultPrice->multiply($pricingGroupCoefficient);
                    $maxInputPrice = $defaultMaxInputPrice->multiply($pricingGroupCoefficient);
                }
            }
        }

        $isPriceFrom = false;
        if ($product->isMainVariant() && $maxInputPrice->isGreaterThan($inputPrice)) {
            $isPriceFrom = true;
        }

        if ($productActionPrice->isZero() === false && $inputPrice->isGreaterThan($productActionPrice)) {
            $inputPrice = $productActionPrice;
        }

        $defaultCurrency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainId);

        $defaultPrice = $this->basePriceCalculation->calculateBasePriceRoundedByCurrency(
            $defaultPrice,
            $this->pricingSetting->getInputPriceType(),
            $product->getVatForDomain($domainId),
            $defaultCurrency
        );

        if ($product->isGiftCertificate()) {
            $basePrice = $defaultPrice;
        } else {
            $basePrice = $this->basePriceCalculation->calculateBasePriceRoundedByCurrency(
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
            $pricingGroup,
            $defaultPricingGroup,
            $product->getActionPrice($domainId),
            $defaultPrice
        );
    }
}
