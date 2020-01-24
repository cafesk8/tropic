<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Pricing;

use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Component\Setting\Setting;
use Shopsys\FrameworkBundle\Model\Pricing\BasePriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade;
use Shopsys\FrameworkBundle\Model\Pricing\PricingSetting;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceRepository;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculation as BaseProductPriceCalculation;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository;

/**
 * @property \Shopsys\ShopBundle\Model\Product\Pricing\ProductManualInputPriceRepository $productManualInputPriceRepository
 * @property \Shopsys\ShopBundle\Model\Product\ProductRepository $productRepository
 * @property \Shopsys\ShopBundle\Model\Pricing\Currency\CurrencyFacade|null $currencyFacade
 * @method setCurrencyFacade(\Shopsys\ShopBundle\Model\Pricing\Currency\CurrencyFacade $currencyFacade)
 * @method \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice calculateMainVariantPrice(\Shopsys\ShopBundle\Model\Product\Product $mainVariant, int $domainId, \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup)
 */
class ProductPriceCalculation extends BaseProductPriceCalculation
{
    /**
     * @var \Shopsys\ShopBundle\Component\Setting\Setting
     */
    private $setting;

    /**
     * @var \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\BasePriceCalculation $basePriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Pricing\PricingSetting $pricingSetting
     * @param \Shopsys\ShopBundle\Model\Product\Pricing\ProductManualInputPriceRepository $productManualInputPriceRepository
     * @param \Shopsys\ShopBundle\Model\Product\ProductRepository $productRepository
     * @param \Shopsys\ShopBundle\Component\Setting\Setting $setting
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     */
    public function __construct(
        BasePriceCalculation $basePriceCalculation,
        PricingSetting $pricingSetting,
        ProductManualInputPriceRepository $productManualInputPriceRepository,
        ProductRepository $productRepository,
        Setting $setting,
        PricingGroupFacade $pricingGroupFacade
    ) {
        parent::__construct($basePriceCalculation, $pricingSetting, $productManualInputPriceRepository, $productRepository);
        $this->setting = $setting;
        $this->pricingGroupFacade = $pricingGroupFacade;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param int $domainId
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice
     */
    public function calculatePrice(Product $product, $domainId, PricingGroup $pricingGroup)
    {
        return $this->calculateProductPriceForPricingGroup($product, $pricingGroup);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice
     */
    protected function calculateProductPriceForPricingGroup(Product $product, PricingGroup $pricingGroup)
    {
        $defaultPricingGroup = $this->pricingGroupFacade->getById(
            $this->setting->getForDomain(Setting::DEFAULT_PRICING_GROUP, $pricingGroup->getDomainId())
        );

        $manualInputPrices = $this->productManualInputPriceRepository->findByProductAndPricingGroupsForDomain($product, [
            $pricingGroup,
            $defaultPricingGroup,
        ], $pricingGroup->getDomainId());

        $inputPrice = Money::zero();
        $defaultPrice = Money::zero();
        $productActionPrice = Money::zero();

        foreach ($manualInputPrices as $manualInputPrice) {
            if ($manualInputPrice !== null) {
                ['inputPrice' => $calculatedInputPrice, 'pricingGroupId' => $pricingGroupId, 'actionPrice' => $actionPrice] = $manualInputPrice;

                $productActionPrice = $actionPrice ? Money::create($actionPrice) : Money::zero();

                if ($pricingGroupId === $defaultPricingGroup->getId() && $calculatedInputPrice !== null) {
                    $defaultPrice = Money::create($calculatedInputPrice) ?? Money::zero();
                }

                if ($pricingGroupId === $pricingGroup->getId() && $calculatedInputPrice !== null) {
                    $inputPrice = Money::create($calculatedInputPrice) ?? Money::zero();
                }
            }
        }

        if ($productActionPrice->isZero() === false && $inputPrice->isGreaterThan($productActionPrice)) {
            $inputPrice = $productActionPrice;
        }

        $defaultPrice = $this->basePriceCalculation->calculateBasePrice(
            $defaultPrice,
            $this->pricingSetting->getInputPriceType(),
            $product->getVat()
        );

        if ($product->isProductTypeGiftCertificate()) {
            $basePrice = $defaultPrice;
        } else {
            $basePrice = $this->basePriceCalculation->calculateBasePrice(
                $inputPrice,
                $this->pricingSetting->getInputPriceType(),
                $product->getVat()
            );
        }

        return new ProductPrice(
            $basePrice,
            false,
            $pricingGroup,
            $defaultPricingGroup,
            $product->getActionPrice($pricingGroup->getDomainId()),
            $defaultPrice
        );
    }
}
