<?php

declare(strict_types=1);

namespace App\Model\Feed\Google;

use App\Model\Category\CategoryFacade;
use App\Model\Feed\FeedHelper;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Product\Collection\ProductUrlsBatchLoader;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForCustomerUser;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\ProductFeed\GoogleBundle\Model\FeedItem\GoogleFeedItem as BaseGoogleFeedItem;
use Shopsys\ProductFeed\GoogleBundle\Model\FeedItem\GoogleFeedItemFactory as BaseGoogleFeedItemFactory;

class GoogleFeedItemFactory extends BaseGoogleFeedItemFactory
{
    /**
     * @var \App\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \App\Model\Feed\FeedHelper
     */
    private $feedHelper;

    /**
     * @param \App\Model\Product\Pricing\ProductPriceCalculationForCustomerUser $productPriceCalculationForUser
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \App\Model\Product\Collection\ProductUrlsBatchLoader $productUrlsBatchLoader
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Model\Feed\FeedHelper $feedHelper
     */
    public function __construct(
        ProductPriceCalculationForCustomerUser $productPriceCalculationForUser,
        CurrencyFacade $currencyFacade,
        ProductUrlsBatchLoader $productUrlsBatchLoader,
        CategoryFacade $categoryFacade,
        FeedHelper $feedHelper
    ) {
        parent::__construct($productPriceCalculationForUser, $currencyFacade, $productUrlsBatchLoader);
        $this->categoryFacade = $categoryFacade;
        $this->feedHelper = $feedHelper;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \App\Model\Feed\Google\GoogleFeedItem
     */
    public function create(Product $product, DomainConfig $domainConfig): BaseGoogleFeedItem
    {
        $mainProduct = $product->isVariant() ? $product->getMainVariant() : $product;
        $brandName = $this->getBrandName($product);
        $productName = $this->feedHelper->createProductName($product, $domainConfig, $brandName);
        $categoryFullPath = $this->getCategoryFullPath($product, $domainConfig);

        $googleFeedItem = new GoogleFeedItem(
            $product->getId(),
            $productName,
            $this->getBrandName($product),
            $mainProduct->getDescription($domainConfig->getId()),
            $product->getEan(),
            $product->getPartno(),
            $this->productUrlsBatchLoader->getProductUrl($product, $domainConfig),
            $this->productUrlsBatchLoader->getProductImageUrl($mainProduct, $domainConfig),
            $product->getCalculatedSellingDenied(),
            $this->getPrice($product, $domainConfig),
            $this->getCurrency($domainConfig)
        );

        $mainVariantId = $product->isVariant() ? $product->getMainVariant()->getId() : null;
        $googleFeedItem->setGroupId($mainVariantId);
        $googleFeedItem->setCategoryFullPath($categoryFullPath);

        return $googleFeedItem;
    }

    /**
     * @param \App\Model\Product\Product $product
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
