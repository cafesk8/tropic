<?php

declare(strict_types=1);

namespace App\Model\Product\View;

use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Product\Group\ProductGroupFacade;
use App\Model\Product\Pricing\ProductPrice;
use App\Model\Product\ProductFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup as BasePricingGroup;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice as BaseProductPrice;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade;
use Shopsys\ReadModelBundle\Image\ImageView;
use Shopsys\ReadModelBundle\Product\Action\ProductActionView as BaseProductActionView;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductView as BaseListedProductView;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFactory as BaseListedProductViewFactory;

/**
 * @property \App\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
 */
class ListedProductViewFactory extends BaseListedProductViewFactory
{
    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \App\Model\Product\Group\ProductGroupFacade
     */
    private $productGroupFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Product\Group\ProductGroupFacade $productGroupFacade
     */
    public function __construct(
        Domain $domain,
        ProductCachedAttributesFacade $productCachedAttributesFacade,
        ProductFacade $productFacade,
        PricingGroupFacade $pricingGroupFacade,
        ProductGroupFacade $productGroupFacade
    ) {
        parent::__construct($domain, $productCachedAttributesFacade);
        $this->productFacade = $productFacade;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->productGroupFacade = $productGroupFacade;
    }

    /**
     * @param array $productArray
     * @param \Shopsys\ReadModelBundle\Image\ImageView|null $imageView
     * @param \App\Model\Product\View\ProductActionView $productActionView
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\View\ListedProductView
     */
    public function createFromArray(
        array $productArray,
        ?ImageView $imageView,
        BaseProductActionView $productActionView,
        BasePricingGroup $pricingGroup
    ): BaseListedProductView {
        $sellingPrice = $this->getSellingPrice(
            $productArray['prices'],
            $pricingGroup,
            $productArray['id'],
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
            $imageView,
            $productArray['gifts'],
            $productArray['stock_quantity'],
            $productArray['variants_count'],
            $productArray['group_items']
        );
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\ReadModelBundle\Image\ImageView|null $imageView
     * @param \Shopsys\ReadModelBundle\Product\Action\ProductActionView $productActionView
     * @return \App\Model\Product\View\ListedProductView
     */
    public function createFromProduct(
        Product $product,
        ?ImageView $imageView,
        BaseProductActionView $productActionView
    ): BaseListedProductView {
        return new ListedProductView(
            $product->getId(),
            $product->getName(),
            $product->getShortDescription($this->domain->getId()),
            $product->getCalculatedAvailability()->getName(),
            $this->productCachedAttributesFacade->getProductSellingPrice($product),
            $this->getFlagIdsForProduct($product),
            $productActionView,
            $imageView,
            $this->productFacade->getProductGiftNames($product, $this->domain->getId(), $this->domain->getLocale()),
            $product->getStockQuantity(),
            $product->getVariantsCount(),
            $this->productGroupFacade->getAllForElasticByMainProduct($product, $this->domain->getLocale())
        );
    }

    /**
     * @param array $pricesArray
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param int $productId
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $defaultProductPrice
     * @return \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice|null
     */
    private function getSellingPrice(
        array $pricesArray,
        BasePricingGroup $pricingGroup,
        int $productId,
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
                    $defaultProductPrice,
                    $this->getStandardPrice($pricesArray)
                );
            }
        }

        return null;
    }

    /**
     * @param array $pricesArray
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price|null
     */
    private function getStandardPrice(array $pricesArray): ?Price
    {
        $standardPricePricingGroupId = $this->pricingGroupFacade->getStandardPricePricingGroup($this->domain->getId())->getId();

        foreach ($pricesArray as $priceArray) {
            if ($priceArray['pricing_group_id'] === $standardPricePricingGroupId) {
                return $this->getPriceFromPriceArray($priceArray);
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
}
