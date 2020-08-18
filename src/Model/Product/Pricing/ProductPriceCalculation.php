<?php

declare(strict_types=1);

namespace App\Model\Product\Pricing;

use App\Component\Domain\DomainHelper;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Model\Pricing\BasePriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup as BasePricingGroup;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade;
use Shopsys\FrameworkBundle\Model\Pricing\PricingSetting;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceRepository;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculation as BaseProductPriceCalculation;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository;

/**
 * @property \App\Model\Product\Pricing\ProductManualInputPriceRepository $productManualInputPriceRepository
 * @property \App\Model\Product\ProductRepository $productRepository
 * @method setCurrencyFacade(\App\Model\Pricing\Currency\CurrencyFacade $currencyFacade)
 * @method \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice calculateMainVariantPrice(\App\Model\Product\Product $mainVariant, int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @property \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
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
    public function calculatePrice(Product $product, $domainId, BasePricingGroup $pricingGroup)
    {
        return $this->calculateProductPriceForPricingGroup($product, $pricingGroup);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Pricing\ProductPrice
     */
    protected function calculateProductPriceForPricingGroup(Product $product, BasePricingGroup $pricingGroup)
    {
        $domainId = $pricingGroup->getDomainId();
        $defaultCurrency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainId);

        $defaultPricingGroup = $this->pricingGroupFacade->getById(
            $this->setting->getForDomain(Setting::DEFAULT_PRICING_GROUP, $domainId)
        );

        $matchingPricingGroupOnFirstDomain = $pricingGroup->getInternalId() !== null ? $this->pricingGroupFacade->getByNameAndDomainId($pricingGroup->getInternalId(), DomainHelper::CZECH_DOMAIN) : null;
        $standardPricingGroup = $this->pricingGroupFacade->getStandardPricePricingGroup($domainId);
        $salePricingGroup = $this->pricingGroupFacade->getSalePricePricingGroup($domainId);
        $pricingGroups = [$pricingGroup, $defaultPricingGroup, $standardPricingGroup, $salePricingGroup];

        if ($matchingPricingGroupOnFirstDomain !== null) {
            $pricingGroups[] = $matchingPricingGroupOnFirstDomain;
        }

        $manualInputPrices = $this->productManualInputPriceRepository->findByProductAndPricingGroupsForDomain($product, $pricingGroups, $domainId);

        if ($product->isMainVariant() && count($manualInputPrices) === 0) {
            $manualInputPrices = $this->productManualInputPriceRepository->findByProductAndPricingGroupsForDomain($product, $pricingGroups, $domainId, true);
        }

        list($defaultPrice, $defaultMaxInputPrice) = $this->getDefaultPrices($manualInputPrices, $defaultPricingGroup->getId());

        $inputPrice = Money::zero();
        $maxInputPrice = Money::zero();
        $standardPriceInput = null;
        $defaultCurrencyPriceInput = null;
        $maxDefaultCurrencyPriceInput = null;

        foreach ($manualInputPrices as $manualInputPrice) {
            if ($manualInputPrice !== null) {
                if ($manualInputPrice['pricingGroupId'] === $pricingGroup->getId()) {
                    if (!$pricingGroup->isCalculatedFromDefault()) {
                        $inputPrice = $manualInputPrice['inputPrice'] ? Money::create($manualInputPrice['inputPrice']) : Money::zero();
                        $maxInputPrice = $defaultMaxInputPrice;
                    } elseif ($product->isRegistrationDiscountDisabled() || $product->isInAnySaleStock()) {
                        $inputPrice = $defaultPrice;
                        $maxInputPrice = $defaultMaxInputPrice;
                    } elseif ($manualInputPrice['inputPrice'] !== null) {
                        $pricingGroupCoefficient = strval($pricingGroup->getDiscountCoefficient());
                        $inputPrice = $defaultPrice->multiply($pricingGroupCoefficient);
                        $maxInputPrice = $defaultMaxInputPrice->multiply($pricingGroupCoefficient);
                    }
                } elseif ($manualInputPrice['pricingGroupId'] === $standardPricingGroup->getId() && $manualInputPrice['inputPrice'] !== null) {
                    $standardPriceInput = Money::create($manualInputPrice['inputPrice']);
                } elseif ($matchingPricingGroupOnFirstDomain !== null &&
                    $manualInputPrice['pricingGroupId'] === $matchingPricingGroupOnFirstDomain->getId() &&
                    $manualInputPrice['inputPrice'] !== null
                ) {
                    $defaultCurrencyPriceInput = Money::create($manualInputPrice['inputPrice']);
                    $maxDefaultCurrencyPriceInput = Money::create($manualInputPrice['maxInputPrice']);
                }
            }
        }

        $isPriceFrom = false;
        if ($product->isMainVariant() && $maxInputPrice->isGreaterThan($inputPrice)) {
            $isPriceFrom = true;
        }

        $defaultCurrency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainId);

        $defaultPrice = $this->basePriceCalculation->calculateBasePriceRoundedByCurrency(
            $defaultPrice,
            $this->pricingSetting->getInputPriceType(),
            $product->getVatForDomain($domainId),
            $defaultCurrency
        );

        $basePrice = $this->basePriceCalculation->calculateBasePriceRoundedByCurrency(
            $inputPrice,
            $this->pricingSetting->getInputPriceType(),
            $product->getVatForDomain($domainId),
            $defaultCurrency
        );

        $standardPrice = null;

        if ($standardPriceInput !== null) {
            $standardPrice = $this->basePriceCalculation->calculateBasePriceRoundedByCurrency(
                $standardPriceInput,
                $this->pricingSetting->getInputPriceType(),
                $product->getVatForDomain($domainId),
                $defaultCurrency
            );
        }

        return new ProductPrice(
            $basePrice,
            $isPriceFrom,
            $product->getId(),
            $defaultPrice,
            $standardPrice
        );
    }

    /**
     * @param array $manualInputPrices
     * @param int $defaultPricingGroupId
     * @return \Shopsys\FrameworkBundle\Component\Money\Money[]
     */
    private function getDefaultPrices(array $manualInputPrices, int $defaultPricingGroupId): array
    {
        $defaultPrices = [Money::zero(), Money::zero()];

        foreach ($manualInputPrices as $manualInputPrice) {
            if ($manualInputPrice !== null && $manualInputPrice['pricingGroupId'] === $defaultPricingGroupId && $manualInputPrice['inputPrice'] !== null) {
                $defaultPrices[0] = Money::create($manualInputPrice['inputPrice']);
                $defaultPrices[1] = Money::create($manualInputPrice['maxInputPrice']);
                break;
            }
        }

        return $defaultPrices;
    }
}
