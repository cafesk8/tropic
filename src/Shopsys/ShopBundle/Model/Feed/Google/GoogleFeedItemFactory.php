<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Feed\Google;

use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Product\Collection\ProductUrlsBatchLoader;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\ProductFeed\GoogleBundle\Model\FeedItem\GoogleFeedItem as BaseGoogleFeedItem;
use Shopsys\ProductFeed\GoogleBundle\Model\FeedItem\GoogleFeedItemFactory as BaseGoogleFeedItemFactory;
use Shopsys\ShopBundle\Model\Category\CategoryFacade;
use Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade;

class GoogleFeedItemFactory extends BaseGoogleFeedItemFactory
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade
     */
    private $productCachedAttributesFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser $productPriceCalculationForUser
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Collection\ProductUrlsBatchLoader $productUrlsBatchLoader
     * @param \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     * @param \Shopsys\ShopBundle\Model\Category\CategoryFacade $categoryFacade
     */
    public function __construct(
        ProductPriceCalculationForUser $productPriceCalculationForUser,
        CurrencyFacade $currencyFacade,
        ProductUrlsBatchLoader $productUrlsBatchLoader,
        ProductCachedAttributesFacade $productCachedAttributesFacade,
        CategoryFacade $categoryFacade
    ) {
        parent::__construct($productPriceCalculationForUser, $currencyFacade, $productUrlsBatchLoader);
        $this->productCachedAttributesFacade = $productCachedAttributesFacade;
        $this->categoryFacade = $categoryFacade;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \Shopsys\ShopBundle\Model\Feed\Google\GoogleFeedItem
     */
    public function create(Product $product, DomainConfig $domainConfig): BaseGoogleFeedItem
    {
        $mainProduct = $product->isVariant() ? $product->getMainVariant() : $product;
        $productName = $this->createProductName($product, $domainConfig);
        $categoryFullPath = $this->getCategoryFullPath($product, $domainConfig);

        $googleFeedItem = new GoogleFeedItem(
            $product->getId(),
            $productName,
            $this->getBrandName($product),
            $mainProduct->getDescription($domainConfig->getId()),
            $product->getEan(),
            $product->getPartno(),
            $this->productUrlsBatchLoader->getProductUrl($product, $domainConfig),
            $this->productUrlsBatchLoader->getProductImageUrl($product, $domainConfig),
            $product->getCalculatedSellingDenied(),
            $this->getPrice($product, $domainConfig),
            $this->getCurrency($domainConfig)
        );

        $googleFeedItem->setCatnum($product->getCatnum());
        $googleFeedItem->setCategoryFullPath($categoryFullPath);

        return $googleFeedItem;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return string
     */
    private function createProductName(Product $product, DomainConfig $domainConfig): string
    {
        $productName = $product->getName($domainConfig->getLocale());

        if ($product->getVariantType() === Product::VARIANT_TYPE_NONE) {
            return $productName;
        }

        $productDistinguishingParameterValue = $this->productCachedAttributesFacade->getProductDistinguishingParameterValue(
            $product,
            $domainConfig->getLocale()
        );

        $brandName = $this->getBrandName($product);
        $colorValue = $productDistinguishingParameterValue->getFirstDistinguishingParameterValue();
        $sizeValue = $productDistinguishingParameterValue->getSecondDistinguishingParameterValue();

        return sprintf('%s %s %s %s', $brandName, $productName, $colorValue, $sizeValue);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return string|null
     */
    private function getCategoryFullPath(Product $product, DomainConfig $domainConfig): ?string
    {
        $mainCategory = $this->categoryFacade->findProductMainCategoryByDomainId($product, $domainConfig->getId());

        if ($mainCategory === null) {
            return null;
        }

        $categories = $this->categoryFacade->getVisibleCategoriesInPathFromRootOnDomain(
            $mainCategory,
            $domainConfig->getId()
        );

        $categoryFullPath = null;
        $categoryNames = [];
        foreach ($categories as $category) {
            $categoryNames[] = $category->getName($domainConfig->getLocale());
        }

        return $categoryFullPath ?? implode(' &gt; ', $categoryNames);
    }
}
