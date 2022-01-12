<?php

declare(strict_types=1);

namespace App\Model\LuigisBox;

use App\Component\Image\ImageFacade;
use App\Component\Router\DomainRouterFactory;
use App\Model\Category\Category;
use App\Model\Product\Availability\AvailabilityFacade;
use App\Model\Product\Brand\Brand;
use App\Model\Product\Collection\ProductUrlsBatchLoader;
use App\Model\Product\Flag\FlagFacade;
use App\Model\Product\Product;
use App\Model\Product\ProductFacade;
use App\Twig\PriceExtension;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\Image\Exception\ImageNotFoundException;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Product\Collection\Exception\ProductImageUrlNotLoadedException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LuigisBoxObjectFactory
{
    private const TYPE_BRAND = 'brand';
    private const TYPE_CATEGORY = 'category';
    private const TYPE_PRODUCT = 'product';
    private const TYPE_SET = 'set';
    private const TYPE_VARIANT = 'variant';

    private ProductUrlsBatchLoader $productUrlsBatchLoader;

    private ProductFacade $productFacade;

    private PriceExtension $priceExtension;

    private ImageFacade $imageFacade;

    private AvailabilityFacade $availabilityFacade;

    private DomainRouterFactory $domainRouterFactory;

    private FlagFacade $flagFacade;

    /**
     * @param \App\Model\Product\Collection\ProductUrlsBatchLoader $productUrlsBatchLoader
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Twig\PriceExtension $priceExtension
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade
     * @param \App\Component\Router\DomainRouterFactory $domainRouterFactory
     * @param \App\Model\Product\Flag\FlagFacade $flagFacade
     */
    public function __construct(
        ProductUrlsBatchLoader $productUrlsBatchLoader,
        ProductFacade $productFacade,
        PriceExtension $priceExtension,
        ImageFacade $imageFacade,
        AvailabilityFacade $availabilityFacade,
        DomainRouterFactory $domainRouterFactory,
        FlagFacade $flagFacade
    ) {
        $this->productUrlsBatchLoader = $productUrlsBatchLoader;
        $this->productFacade = $productFacade;
        $this->priceExtension = $priceExtension;
        $this->imageFacade = $imageFacade;
        $this->availabilityFacade = $availabilityFacade;
        $this->domainRouterFactory = $domainRouterFactory;
        $this->flagFacade = $flagFacade;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param string $type
     * @return \App\Model\LuigisBox\LuigisBoxObject
     */
    public function createProduct(Product $product, DomainConfig $domainConfig, string $type = self::TYPE_PRODUCT): LuigisBoxObject
    {
        if ($product->isPohodaProductTypeSet()) {
            $type = self::TYPE_SET;
        }

        $domainId = $domainConfig->getId();
        $brand = $product->getBrand();

        $luigisProduct = new LuigisBoxObject();
        $luigisProduct->type = $type;
        $luigisProduct->url = $product->getCatnum();
        $luigisProduct->fields = $this->mapProductFields($product, $domainConfig);
        $luigisProduct->fields->web_url = $this->productUrlsBatchLoader->getProductUrl($product, $domainConfig);

        if ($brand !== null) {
            $luigisProduct->nested[] = $this->createBrand($brand, $domainConfig);
        }

        foreach ($product->getVisibleVariants() as $variant) {
            $luigisProduct->nested[] = $this->createProduct($variant, $domainConfig, self::TYPE_VARIANT);
        }

        foreach ($product->getCategoriesIndexedByDomainId()[$domainId] as $category) {
            $luigisProduct->nested[] = $this->createCategory($category, $domainConfig);
        }

        return $luigisProduct;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \App\Model\LuigisBox\LuigisBoxProductFields
     */
    private function mapProductFields(Product $product, DomainConfig $domainConfig): LuigisBoxProductFields
    {
        $domainId = $domainConfig->getId();
        $locale = $domainConfig->getLocale();
        $gift = $product->getFirstActiveInStockProductGiftByDomainId($domainId);

        $productFields = new LuigisBoxProductFields();
        $productFields->domain_id = $domainId;
        $productFields->title = $product->getName($locale) ?? '';
        $productFields->availability = $product->getCalculatedSellingDenied() ? 0 : 1;
        $productFields->availability_color = $product->getCalculatedAvailability()->getRgbColor();
        $productFields->availability_rating = $product->getCalculatedAvailability()->getRating();
        $productFields->availability_text = $this->availabilityFacade->getAvailabilityText($product, $locale);
        $productFields->code = $product->getCatnum();
        $productFields->description = $product->getDescription($domainId);
        $productFields->description_short = $product->getShortDescription($domainId);
        $productFields->id = $product->getId();
        $productFields->image_link = $this->mapProductImages($product, $domainConfig);
        $productFields->in_sale = $product->isInAnySaleStock();
        $productFields->visible = $product->isVisible();
        $this->mapPrices($productFields, $this->productFacade->getAllProductSellingPricesByDomainId($product, $domainId), $domainId);
        $this->mapFlags($productFields, $product, $locale);

        if (!$product->isMainVariant()) {
            $productFields->maximum_quantity = $product->getRealStockQuantity();
            $productFields->minimum_quantity = $product->getMinimumAmount();
            $productFields->quantity_multiplier = $product->getAmountMultiplier();
        }

        if ($gift !== null) {
            $productFields->gift = $gift->getGift()->getName($locale);
        }

        foreach ($product->getProductSets() as $productSet) {
            $setItem = $productSet->getItem();
            $productFields->set_items[] = [
                'title' => $setItem->getName($locale),
                'image_link' => $this->mapProductImages($setItem, $domainConfig),
                'quantity' => $productSet->getItemCount(),
            ];
        }

        return $productFields;
    }

    /**
     * @param \App\Model\Category\Category $category
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param bool $includeAncestors
     * @return \App\Model\LuigisBox\LuigisBoxObject
     */
    private function createCategory(Category $category, DomainConfig $domainConfig, bool $includeAncestors = true): LuigisBoxObject
    {
        $domainId = $domainConfig->getId();
        $domainRouter = $this->domainRouterFactory->getRouter($domainId);

        $luigisCategory = new LuigisBoxObject();
        $luigisCategory->type = self::TYPE_CATEGORY;
        $luigisCategory->url = (string)$category->getId();
        $luigisCategory->fields = new LuigisBoxCategoryFields();
        $luigisCategory->fields->domain_id = $domainId;
        $luigisCategory->fields->title = $category->getName($domainConfig->getLocale());
        $luigisCategory->fields->web_url = $domainRouter->generate('front_product_list', [
            'id' => $category->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            $luigisCategory->fields->image_link = $this->imageFacade->getImageUrl($domainConfig, $category, null, null);
        } catch (ImageNotFoundException $exception) {}

        if ($includeAncestors) {
            $parentCategory = $category->getParent();

            while ($parentCategory !== null && !empty($parentCategory->getName($domainConfig->getLocale()))) {
                $luigisCategory->fields->ancestors[] = $this->createCategory($parentCategory, $domainConfig, false);
                $parentCategory = $parentCategory->getParent();
            }
        }

        return $luigisCategory;
    }

    /**
     * @param \App\Model\Product\Brand\Brand $brand
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \App\Model\LuigisBox\LuigisBoxObject
     */
    private function createBrand(Brand $brand, DomainConfig $domainConfig): LuigisBoxObject
    {
        $domainId = $domainConfig->getId();
        $domainRouter = $this->domainRouterFactory->getRouter($domainId);

        $luigisBrand = new LuigisBoxObject();
        $luigisBrand->type = self::TYPE_BRAND;
        $luigisBrand->url = (string)$brand->getId();
        $luigisBrand->fields = new LuigisBoxBrandFields();
        $luigisBrand->fields->title = $brand->getName();
        $luigisBrand->fields->web_url = $domainRouter->generate('front_brand_detail', [
            'id' => $brand->getId(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            $luigisBrand->fields->image_link = $this->imageFacade->getImageUrl($domainConfig, $brand, null, null);
        } catch (ImageNotFoundException $exception) {}

        return $luigisBrand;
    }

    /**
     * @param \App\Model\Product\Product[] $products
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     */
    public function loadUrls(array $products, DomainConfig $domainConfig): void
    {
        $productsWithVariantsAndSetItems = $products;

        foreach ($products as $product) {
            foreach ($product->getVariants($domainConfig->getLocale()) as $variant) {
                $productsWithVariantsAndSetItems[] = $variant;
            }

            foreach ($product->getProductSets() as $productSet) {
                $productsWithVariantsAndSetItems[] = $productSet->getItem();
            }
        }

        $this->productUrlsBatchLoader->loadForProducts($productsWithVariantsAndSetItems, $domainConfig);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return string|null
     */
    private function mapProductImages(Product $product, DomainConfig $domainConfig): ?string
    {
        try {
            return $this->productUrlsBatchLoader->getProductImageUrl($product, $domainConfig);
        } catch (ProductImageUrlNotLoadedException $exception) {}

        return null;
    }

    /**
     * @param \App\Model\LuigisBox\LuigisBoxProductFields $luigisProductFields
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductSellingPrice[] $prices
     * @param int $domainId
     */
    private function mapPrices(LuigisBoxProductFields $luigisProductFields, array $prices, int $domainId): void
    {
        $ordinaryPrice = Money::zero();
        $registeredPrice = Money::zero();
        $standardPrice = Money::zero();
        $salePrice = Money::zero();

        foreach ($prices as $price) {
            /** @var \App\Model\Pricing\Group\PricingGroup $pricingGroup */
            $pricingGroup = $price->getPricingGroup();
            $sellingPrice = $price->getSellingPrice()->getPriceWithVat();

            if ($pricingGroup->isOrdinaryCustomerPricingGroup()) {
                $ordinaryPrice = $sellingPrice;
                $luigisProductFields->price_amount = $sellingPrice->getAmount();
                $luigisProductFields->price = $this->priceExtension->priceWithCurrencyByDomainIdFilter($sellingPrice, $domainId);
            } elseif ($pricingGroup->isRegisteredCustomerPricingGroup()) {
                $registeredPrice = $sellingPrice;
                $luigisProductFields->price_registered_amount = $sellingPrice->getAmount();
                $luigisProductFields->price_registered = $this->priceExtension->priceWithCurrencyByDomainIdFilter($sellingPrice, $domainId);
            } elseif ($pricingGroup->isStandardPricePricingGroup()) {
                $standardPrice = $sellingPrice;
                $luigisProductFields->price_standard_amount = $sellingPrice->getAmount();
                $luigisProductFields->price_standard = $this->priceExtension->priceWithCurrencyByDomainIdFilter($sellingPrice, $domainId);
            } elseif ($pricingGroup->isSalePricePricingGroup()) {
                $salePrice = $sellingPrice;
                $luigisProductFields->price_sale_amount = $sellingPrice->getAmount();
                $luigisProductFields->price_sale = $this->priceExtension->priceWithCurrencyByDomainIdFilter($sellingPrice, $domainId);
            }
        }

        if ($luigisProductFields->in_sale && $standardPrice->isGreaterThan(Money::zero())) {
            $luigisProductFields->standard_discount_percent = intval($standardPrice->subtract($salePrice)->divide($standardPrice->getAmount(), 3)->multiply(100)->getAmount());
            $luigisProductFields->registered_discount_percent = intval($standardPrice->subtract($registeredPrice)->divide($standardPrice->getAmount(), 3)->multiply(100)->getAmount());
        } elseif ($ordinaryPrice->isGreaterThan(Money::zero()) && $standardPrice->isGreaterThan(Money::zero())) {
            $luigisProductFields->standard_discount_percent = intval($standardPrice->subtract($ordinaryPrice)->divide($standardPrice->getAmount(), 3)->multiply(100)->getAmount());
            $luigisProductFields->registered_discount_percent = intval($standardPrice->subtract($registeredPrice)->divide($standardPrice->getAmount(), 3)->multiply(100)->getAmount());
        }
    }

    /**
     * @param \App\Model\LuigisBox\LuigisBoxProductFields $luigisProductFields
     * @param \App\Model\Product\Product $product
     * @param string $locale
     */
    private function mapFlags(LuigisBoxProductFields $luigisProductFields, Product $product, string $locale): void
    {
        $saleFlagUsed = false;

        foreach ($product->getActiveFlags() as $flag) {
            if ($saleFlagUsed && ($flag->isSale() || $flag->isClearance())) {
                continue;
            }

            if ($flag->isClearance()) {
                $flag = $this->flagFacade->getSaleFlag();
            }

            $luigisProductFields->flags[] = $flag->getName($locale);
            $luigisProductFields->flag_colors[] = $flag->getRgbColor();

            if ($flag->isSale()) {
                $saleFlagUsed = true;
            }
        }
    }
}