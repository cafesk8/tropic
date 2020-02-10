<?php

declare(strict_types = 1);

namespace App\Model\Feed\HsSport\FeedItem;

use App\Model\Product\Parameter\ParameterFacade;
use App\Model\Product\Pricing\ProductPriceCalculation;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\Image\Exception\ImageNotFoundException;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Product;

class HsSportFeedItemFactory
{
    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @var \App\Component\Image\ImageFacade
     */
    private $imageFacade;

    /**
     * @var \App\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    private $pricingGroupSettingFacade;

    /**
     * @var \App\Model\Product\Pricing\ProductPriceCalculation
     */
    private $productPriceCalculation;

    /**
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \App\Model\Product\Parameter\ParameterFacade $parameterFacade
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     * @param \App\Model\Product\Pricing\ProductPriceCalculation $productPriceCalculation
     */
    public function __construct(
        CurrencyFacade $currencyFacade,
        ImageFacade $imageFacade,
        ParameterFacade $parameterFacade,
        PricingGroupSettingFacade $pricingGroupSettingFacade,
        ProductPriceCalculation $productPriceCalculation
    ) {
        $this->currencyFacade = $currencyFacade;
        $this->imageFacade = $imageFacade;
        $this->parameterFacade = $parameterFacade;
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
        $this->productPriceCalculation = $productPriceCalculation;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \App\Model\Feed\HsSport\FeedItem\HsSportFeedItem
     */
    public function create(Product $product, DomainConfig $domainConfig): HsSportFeedItem
    {
        $hsSportVariantItems = $this->getHsSportVariantItemsFromVariants($domainConfig, $product->getVariants());

        return new HsSportFeedItem(
            $product->getId(),
            count($hsSportVariantItems) > 0 ? 1 : 0,
            $product->getCatnum(),
            $product->getName($domainConfig->getLocale()),
            $product->getShortDescription($domainConfig->getId()),
            $product->getDescription($domainConfig->getId()),
            $this->getPrice($product, $domainConfig)->getPriceWithVat()->getAmount(),
            $this->getPrice($product, $domainConfig)->defaultProductPrice()->getPriceWithVat()->getAmount(),
            $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainConfig->getId())->getCode(),
            $this->getAllImagesUrlsByProduct($product, $domainConfig),
            $this->getCategoriesStringsFromCategories(
                $product->getListableProductCategoriesByDomainId($domainConfig->getId()),
                $domainConfig
            ),
            $hsSportVariantItems
        );
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param \App\Model\Product\Product[] $variants
     * @return \App\Model\Feed\HsSport\FeedItem\HsSportFeedVariantItem[]
     */
    protected function getHsSportVariantItemsFromVariants(DomainConfig $domainConfig, array $variants = []): array
    {
        $hsSportVariantItems = [];

        foreach ($variants as $variant) {
            $sizeProductParameterValue = $this->parameterFacade->findSizeProductParameterValueByProductId($variant->getId());
            $colorProductParameterValue = $this->parameterFacade->findColorProductParameterValueByProductId($variant->getId());

            if ($sizeProductParameterValue === null) {
                $sizeValue = '';
            } else {
                /** @var \App\Model\Product\Parameter\ParameterValue $sizeProductParameterValueValue */
                $sizeProductParameterValueValue = $sizeProductParameterValue->getValue();
                $sizeValue = $sizeProductParameterValueValue->getHsFeedId() . '_' . $sizeProductParameterValueValue->getText();
            }

            if ($colorProductParameterValue === null) {
                $colorValue = '';
            } else {
                /** @var \App\Model\Product\Parameter\ParameterValue $colorProductParameterValueValue */
                $colorProductParameterValueValue = $colorProductParameterValue->getValue();
                $colorValue = $colorProductParameterValueValue->getHsFeedId() . '_' . $colorProductParameterValueValue->getText();
            }

            $hsSportVariantItems[] = new HsSportFeedVariantItem(
                $variant->getId(),
                $variant->getEan(),
                $this->getAllImagesUrlsByProduct($variant, $domainConfig),
                $sizeValue,
                $colorValue,
                $variant->getStockQuantity() ?? 0
            );
        }

        return $hsSportVariantItems;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return string[]
     */
    protected function getAllImagesUrlsByProduct(Product $product, DomainConfig $domainConfig): array
    {
        try {
            $imagesUrls = $this->imageFacade->getAllImagesUrlsByEntity($product, $domainConfig);

            if ($product->isMainVariant() === true) {
                foreach ($product->getVariants() as $variant) {
                    $variantUrls = $this->imageFacade->getAllImagesUrlsByEntity($variant, $domainConfig);
                    foreach ($variantUrls as $variantUrl) {
                        $imagesUrls[] = $variantUrl;
                    }
                }
            }

            return $imagesUrls;
        } catch (ImageNotFoundException $imageNotFoundException) {
            return [];
        }
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return string|null
     */
    protected function getBrandName(Product $product): ?string
    {
        $brand = $product->getBrand();

        return $brand !== null ? $brand->getName() : null;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \App\Model\Product\Pricing\ProductPrice
     */
    protected function getPrice(Product $product, DomainConfig $domainConfig): Price
    {
        return $this->productPriceCalculation->calculatePrice(
            $product,
            $domainConfig->getId(),
            $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($domainConfig->getId())
        );
    }

    /**
     * @param \App\Model\Category\Category[] $categories
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return string[]
     */
    private function getCategoriesStringsFromCategories(array $categories, DomainConfig $domainConfig): array
    {
        $categoriesStrings = [];
        foreach ($categories as $category) {
            $categoriesStrings[] = $category->getId() . '_' . $category->getName($domainConfig->getLocale());
        }

        return $categoriesStrings;
    }
}
