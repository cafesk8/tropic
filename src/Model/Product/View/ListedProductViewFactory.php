<?php

declare(strict_types=1);

namespace App\Model\Product\View;

use App\Model\Pricing\Group\PricingGroup;
use App\Model\Product\Availability\AvailabilityFacade;
use App\Model\Product\Flag\Flag;
use App\Model\Product\Flag\FlagFacade;
use App\Model\Product\Pricing\ProductPrice;
use App\Model\Product\ProductFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup as BasePricingGroup;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice as BaseProductPrice;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
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
    private ProductFacade $productFacade;

    private ListedSetItemFactory $listedSetItemFactory;

    private ImageViewFacade $imageViewFacade;

    private FlagFacade $flagFacade;

    private AvailabilityFacade $availabilityFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\View\ListedSetItemFactory $listedSetItemFactory
     * @param \App\Model\Product\View\ImageViewFacade $imageViewFacade
     * @param \App\Model\Product\Flag\FlagFacade $flagFacade
     * @param \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade
     */
    public function __construct(
        Domain $domain,
        ProductCachedAttributesFacade $productCachedAttributesFacade,
        ProductFacade $productFacade,
        ListedSetItemFactory $listedSetItemFactory,
        ImageViewFacade $imageViewFacade,
        FlagFacade $flagFacade,
        AvailabilityFacade $availabilityFacade
    ) {
        parent::__construct($domain, $productCachedAttributesFacade);
        $this->productFacade = $productFacade;
        $this->listedSetItemFactory = $listedSetItemFactory;
        $this->imageViewFacade = $imageViewFacade;
        $this->flagFacade = $flagFacade;
        $this->availabilityFacade = $availabilityFacade;
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
            $productArray['id']
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
            $this->listedSetItemFactory->createFromArray($productArray['set_items']),
            $productArray['delivery_days'],
            $productArray['is_available_in_days'],
            $productArray['real_sale_stocks_quantity'],
            $productArray['unit'],
            $this->imageViewFacade->getStickerViewsByProductId($productArray['id']),
            $productArray['warranty'],
            $productArray['recommended'],
            $productArray['supplier_set'],
            $productArray['availability_color']
        );
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\ReadModelBundle\Image\ImageView|null $imageView
     * @param \Shopsys\ReadModelBundle\Product\Action\ProductActionView $productActionView
     * @return \App\Model\Product\View\ListedProductView
     */
    public function createFromProduct(
        BaseProduct $product,
        ?ImageView $imageView,
        BaseProductActionView $productActionView
    ): BaseListedProductView {
        return new ListedProductView(
            $product->getId(),
            $product->getName(),
            $product->getShortDescription($this->domain->getId()),
            $this->availabilityFacade->getAvailabilityText($product, $this->domain->getLocale()),
            $this->productCachedAttributesFacade->getProductSellingPrice($product),
            $this->getFlagIdsForProduct($product),
            $productActionView,
            $imageView,
            $this->productFacade->getProductGiftName($product, $this->domain->getId(), $this->domain->getLocale()),
            $product->getStockQuantity(),
            $product->getVariantsCount($this->domain->getId()),
            $this->listedSetItemFactory->createFromProduct($product, $this->domain->getLocale()),
            $product->isMainVariant() ? '' : $product->getDeliveryDays(),
            $product->isMainVariant() ? false : $product->isAvailableInDays(),
            $product->isSellingDenied() || $product->isMainVariant() ? 0 : $product->getRealSaleStocksQuantity(),
            $product->getUnit()->getName($this->domain->getLocale()),
            $this->imageViewFacade->getStickerViewsByProductId($product->getId()),
            $product->getWarranty(),
            $product->isRecommended(),
            $product->isSupplierSet(),
            $product->getCalculatedAvailability()->getRgbColor()
        );
    }

    /**
     * @param array $pricesArray
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param int $productId
     * @return \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice|null
     */
    private function getSellingPrice(
        array $pricesArray,
        PricingGroup $pricingGroup,
        int $productId
    ): ?BaseProductPrice {
        $pricingGroupId = $pricingGroup->getId();
        $defaultProductPrice = $this->getDefaultPrice($pricesArray);
        $standardPrice = $this->getStandardPrice($pricesArray);
        foreach ($pricesArray as $priceArray) {
            if ($priceArray['pricing_group_id'] === $pricingGroupId) {
                $price = $this->getPriceFromPriceArray($priceArray);
                return new ProductPrice(
                    $price,
                    $priceArray['price_from'],
                    $productId,
                    $defaultProductPrice,
                    $standardPrice
                );
            }
        }

        return null;
    }

    /**
     * @param array $pricesArray
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price|null
     */
    private function getDefaultPrice(array $pricesArray): ?Price
    {
        foreach ($pricesArray as $priceArray) {
            if ($priceArray['is_default']) {
                return $this->getPriceFromPriceArray($priceArray);
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
        foreach ($pricesArray as $priceArray) {
            if ($priceArray['is_standard']) {
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

    /**
     * On FE, we do not want to display "clearance" flag at all, "sale" flag is used instead
     *
     * @param \App\Model\Product\Product $product
     * @return int[]
     */
    protected function getFlagIdsForProduct(BaseProduct $product): array
    {
        $saleFlag = $this->flagFacade->getSaleFlag();
        return array_unique(array_map(function (Flag $flag) use ($saleFlag) {
            if ($flag->isClearance()) {
                return $saleFlag->getId();
            }
            return $flag->getId();
        }, $product->getActiveFlags()));
    }
}
