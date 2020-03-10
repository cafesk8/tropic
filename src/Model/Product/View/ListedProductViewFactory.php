<?php

declare(strict_types=1);

namespace App\Model\Product\View;

use App\Model\Product\Pricing\ProductPrice;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice as BaseProductPrice;
use Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade;
use Shopsys\ReadModelBundle\Image\ImageView;
use Shopsys\ReadModelBundle\Product\Action\ProductActionView;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductView;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductView as BaseListedProductView;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFactory as BaseListedProductViewFactory;

/**
 * @property \App\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
 */
class ListedProductViewFactory extends BaseListedProductViewFactory
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    private $pricingGroupSettingFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     */
    public function __construct(
        Domain $domain,
        ProductCachedAttributesFacade $productCachedAttributesFacade,
        PricingGroupSettingFacade $pricingGroupSettingFacade
    ) {
        parent::__construct($domain, $productCachedAttributesFacade);
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
    }

    /**
     * @param array $productArray
     * @param \Shopsys\ReadModelBundle\Image\ImageView|null $imageView
     * @param \Shopsys\ReadModelBundle\Product\Action\ProductActionView $productActionView
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Shopsys\ReadModelBundle\Product\Listed\ListedProductView
     */
    public function createFromArray(
        array $productArray,
        ?ImageView $imageView,
        ProductActionView $productActionView,
        PricingGroup $pricingGroup
    ): BaseListedProductView {
        $sellingPrice = $this->getSellingPrice(
            $productArray['prices'],
            $pricingGroup,
            $productArray['id'],
            $this->getMoney($productArray['action_price']),
            $this->getPriceFromPriceArray($productArray['default_price'])
        );

        return new ListedProductView(
            $productArray['id'],
            $productArray['name'],
            $productArray['short_description'],
            $productArray['availability'],
            $sellingPrice,
            $productArray['flags'],
            $productActionView,
            $imageView
        );
    }

    /**
     * @param array $pricesArray
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param int $productId
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $actionPriceForCurrentDomain
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $defaultProductPrice
     * @return \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice|null
     */
    private function getSellingPrice(
        array $pricesArray,
        PricingGroup $pricingGroup,
        int $productId,
        ?Money $actionPriceForCurrentDomain,
        ?Price $defaultProductPrice
    ): ?BaseProductPrice {
        $pricingGroupId = $pricingGroup->getId();
        foreach ($pricesArray as $priceArray) {
            if ($priceArray['pricing_group_id'] === $pricingGroupId) {
                $price = $this->getPriceFromPriceArray($priceArray);
                return new ProductPrice(
                    $price,
                    $priceArray['price_from'],
                    $productId,
                    $pricingGroup,
                    $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($pricingGroup->getDomainId()),
                    $actionPriceForCurrentDomain,
                    $defaultProductPrice
                );
            }
        }

        return null;
    }

    /**
     * @param array $priceArray
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    private function getPriceFromPriceArray(array $priceArray): Price
    {
        $priceWithoutVat = Money::create((string)$priceArray['price_without_vat']);
        $priceWithVat = Money::create((string)$priceArray['price_with_vat']);

        return new Price($priceWithoutVat, $priceWithVat);
    }

    /**
     * @param float|null $amount
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    private function getMoney(?float $amount): ?Money
    {
        return $amount !== null ? Money::create((string)$amount) : null;
    }
}
