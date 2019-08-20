<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\Feed\HsSport\FeedItem;

use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\Image\Exception\ImageNotFoundException;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade;
use Shopsys\ShopBundle\Model\Product\Pricing\ProductPriceCalculation;

class HsSportFeedItemFactory
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Image\ImageFacade
     */
    private $imageFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    private $pricingGroupSettingFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Pricing\ProductPriceCalculation
     */
    private $productPriceCalculation;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser $productPriceCalculationForUser
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \Shopsys\ShopBundle\Component\Image\ImageFacade $imageFacade
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade $parameterFacade
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     * @param \Shopsys\ShopBundle\Model\Product\Pricing\ProductPriceCalculation $productPriceCalculation
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
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \Shopsys\ShopBundle\Model\Feed\HsSport\FeedItem\HsSportFeedItem
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
                $product->getProductCategoriesByDomainId($domainConfig->getId()),
                $domainConfig
            ),
            $hsSportVariantItems
        );
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $variants
     * @return \Shopsys\ShopBundle\Model\Feed\HsSport\FeedItem\HsSportFeedVariantItem[]
     */
    protected function getHsSportVariantItemsFromVariants(DomainConfig $domainConfig, array $variants = []): array
    {
        $hsSportVariantItems = [];

        foreach ($variants as $variant) {
            $sizeProductParameterValue = $this->parameterFacade->findSizeProductParameterValueByProductId($variant->getId());
            $colorProductParameterValue = $this->parameterFacade->findColorProductParameterValueByProductId($variant->getId());

            $sizeValue = $sizeProductParameterValue !== null ?
                $sizeProductParameterValue->getValue()->getExternalId() . '_' . $sizeProductParameterValue->getValue()->getText()
                : '';

            $colorValue = $colorProductParameterValue !== null ?
                $colorProductParameterValue->getValue()->getExternalId() . '_' . $colorProductParameterValue->getValue()->getText()
                : '';

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
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \Shopsys\FrameworkBundle\Component\Image\Image[]
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
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @return string|null
     */
    protected function getBrandName(Product $product): ?string
    {
        $brand = $product->getBrand();

        return $brand !== null ? $brand->getName() : null;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \Shopsys\ShopBundle\Model\Product\Pricing\ProductPrice
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
     * @param \Shopsys\ShopBundle\Model\Category\Category[] $categories
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
