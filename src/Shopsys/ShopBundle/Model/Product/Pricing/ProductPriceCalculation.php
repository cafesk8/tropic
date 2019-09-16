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
 */
class ProductPriceCalculation extends BaseProductPriceCalculation
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Setting\Setting
     */
    private $setting;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\BasePriceCalculation $basePriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Pricing\PricingSetting $pricingSetting
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceRepository $productManualInputPriceRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductRepository $productRepository
     * @param \Shopsys\FrameworkBundle\Component\Setting\Setting $setting
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
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
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $mainVariant
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice
     */
    protected function calculateMainVariantPrice(Product $mainVariant, $domainId, PricingGroup $pricingGroup)
    {
        $variants = $this->productRepository->getAllSellableVariantsByMainVariant(
            $mainVariant,
            $domainId,
            $pricingGroup
        );
        if (count($variants) === 0) {
            $message = 'Main variant ID = ' . $mainVariant->getId() . ' has no sellable variants.';
            throw new \Shopsys\FrameworkBundle\Model\Product\Pricing\Exception\MainVariantPriceCalculationException($message);
        }

        $variantPrices = [];
        foreach ($variants as $variant) {
            $variantPrices[] = $this->calculatePrice($variant, $domainId, $pricingGroup);
        }

        $minVariantPrice = $this->getMinimumPriceByPriceWithoutVat($variantPrices);
        $from = $this->arePricesDifferent($variantPrices);

        return new ProductPrice($minVariantPrice, $from);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice
     */
    protected function calculateProductPriceForPricingGroup(Product $product, PricingGroup $pricingGroup)
    {
        $defaultPricingGroup = $this->pricingGroupFacade->getById(
            $this->setting->getForDomain(Setting::DEFAULT_PRICING_GROUP, $pricingGroup->getDomainId())
        );

        $manualInputPrices = $this->productManualInputPriceRepository->findByProductAndPricingGroups($product, [
            $pricingGroup,
            $defaultPricingGroup,
        ]);

        $inputPrice = Money::zero();
        $defaultPrice = Money::zero();

        foreach ($manualInputPrices as $manualInputPrice) {
            if ($manualInputPrice !== null) {
                if ($manualInputPrice->getPricingGroup() === $defaultPricingGroup) {
                    $inputPrice = $manualInputPrice->getInputPrice() ?? Money::zero();
                } else {
                    $defaultPrice = $manualInputPrice->getInputPrice() ?? Money::zero();
                }
            }
        }

        $actionPrice = $product->getActionPrice($pricingGroup->getDomainId());
        if ($actionPrice !== null && $inputPrice->isGreaterThan($actionPrice)) {
            $inputPrice = $actionPrice;
        }

        $basePrice = $this->basePriceCalculation->calculateBasePrice(
            $inputPrice,
            $this->pricingSetting->getInputPriceType(),
            $product->getVat()
        );

        $defaultPrice = $this->basePriceCalculation->calculateBasePrice(
            $defaultPrice,
            $this->pricingSetting->getInputPriceType(),
            $product->getVat()
        );

        return new ProductPrice($basePrice, false, $defaultPrice);
    }
}
