<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Feed\Heureka;

use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\ProductFeed\HeurekaBundle\Model\FeedItem\HeurekaFeedItem;
use Shopsys\ProductFeed\HeurekaBundle\Model\FeedItem\HeurekaFeedItemFactory as BaseHeurekaFeedItemFactory;
use Shopsys\ProductFeed\HeurekaBundle\Model\FeedItem\HeurekaProductDataBatchLoader;
use Shopsys\ProductFeed\HeurekaBundle\Model\HeurekaCategory\HeurekaCategoryFacade;
use Shopsys\ShopBundle\Model\Category\CategoryFacade;
use Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade;

class HeurekaFeedItemFactory extends BaseHeurekaFeedItemFactory
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade
     */
    private $productCachedAttributesFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser $productPriceCalculationForUser
     * @param \Shopsys\ProductFeed\HeurekaBundle\Model\FeedItem\HeurekaProductDataBatchLoader $heurekaProductDataBatchLoader
     * @param \Shopsys\ProductFeed\HeurekaBundle\Model\HeurekaCategory\HeurekaCategoryFacade $heurekaCategoryFacade
     * @param \Shopsys\ShopBundle\Model\Category\CategoryFacade $categoryFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     */
    public function __construct(
        ProductPriceCalculationForUser $productPriceCalculationForUser,
        HeurekaProductDataBatchLoader $heurekaProductDataBatchLoader,
        HeurekaCategoryFacade $heurekaCategoryFacade,
        CategoryFacade $categoryFacade,
        ProductCachedAttributesFacade $productCachedAttributesFacade
    ) {
        parent::__construct(
            $productPriceCalculationForUser,
            $heurekaProductDataBatchLoader,
            $heurekaCategoryFacade,
            $categoryFacade
        );
        $this->productCachedAttributesFacade = $productCachedAttributesFacade;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \Shopsys\ProductFeed\HeurekaBundle\Model\FeedItem\HeurekaFeedItem
     */
    public function create(Product $product, DomainConfig $domainConfig): HeurekaFeedItem
    {
        $mainVariantId = $product->isVariant() ? $product->getMainVariant()->getId() : null;
        $mainProduct = $product->isVariant() ? $product->getMainVariant() : $product;

        $productName = $this->createProductName($product, $domainConfig);

        return new HeurekaFeedItem(
            $product->getId(),
            $mainVariantId,
            $productName,
            $mainProduct->getDescription($domainConfig->getId()),
            $this->productDataBatchLoader->getProductUrl($product, $domainConfig),
            $this->productDataBatchLoader->getProductImageUrl($product, $domainConfig),
            $this->getBrandName($product),
            $product->getEan(),
            $product->getCalculatedAvailability()->getDispatchTime(),
            $this->getPrice($product, $domainConfig),
            $this->getHeurekaCategoryFullName($product, $domainConfig),
            $this->productDataBatchLoader->getProductParametersByName($product, $domainConfig),
            $this->productDataBatchLoader->getProductCpc($product, $domainConfig)
        );
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
}
