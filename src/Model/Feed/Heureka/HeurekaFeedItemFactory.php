<?php

declare(strict_types=1);

namespace App\Model\Feed\Heureka;

use App\Model\Category\CategoryFacade;
use App\Model\Feed\FeedHelper;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForCustomerUser;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\ProductFeed\HeurekaBundle\Model\FeedItem\HeurekaFeedItem;
use Shopsys\ProductFeed\HeurekaBundle\Model\FeedItem\HeurekaFeedItemFactory as BaseHeurekaFeedItemFactory;
use Shopsys\ProductFeed\HeurekaBundle\Model\FeedItem\HeurekaProductDataBatchLoader;
use Shopsys\ProductFeed\HeurekaBundle\Model\HeurekaCategory\HeurekaCategoryFacade;

class HeurekaFeedItemFactory extends BaseHeurekaFeedItemFactory
{
    /**
     * @var \App\Model\Feed\FeedHelper
     */
    private $feedHelper;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForCustomerUser $productPriceCalculationForUser
     * @param \Shopsys\ProductFeed\HeurekaBundle\Model\FeedItem\HeurekaProductDataBatchLoader $heurekaProductDataBatchLoader
     * @param \Shopsys\ProductFeed\HeurekaBundle\Model\HeurekaCategory\HeurekaCategoryFacade $heurekaCategoryFacade
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Model\Feed\FeedHelper $feedHelper
     */
    public function __construct(
        ProductPriceCalculationForCustomerUser $productPriceCalculationForUser,
        HeurekaProductDataBatchLoader $heurekaProductDataBatchLoader,
        HeurekaCategoryFacade $heurekaCategoryFacade,
        CategoryFacade $categoryFacade,
        FeedHelper $feedHelper
    ) {
        parent::__construct(
            $productPriceCalculationForUser,
            $heurekaProductDataBatchLoader,
            $heurekaCategoryFacade,
            $categoryFacade
        );
        $this->feedHelper = $feedHelper;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \Shopsys\ProductFeed\HeurekaBundle\Model\FeedItem\HeurekaFeedItem
     */
    public function create(Product $product, DomainConfig $domainConfig): HeurekaFeedItem
    {
        $mainVariantId = $product->isVariant() ? $product->getMainVariant()->getId() : null;
        $mainProduct = $product->isVariant() ? $product->getMainVariant() : $product;

        $brandName = $this->getBrandName($product);
        $productName = $this->feedHelper->createProductName($product, $domainConfig, $brandName);

        return new HeurekaFeedItem(
            $product->getId(),
            $mainVariantId,
            $productName,
            $mainProduct->getDescription($domainConfig->getId()),
            $this->productDataBatchLoader->getProductUrl($product, $domainConfig),
            $this->productDataBatchLoader->getProductImageUrl($mainProduct, $domainConfig),
            $brandName,
            $product->getEan(),
            $product->getCalculatedAvailability()->getDispatchTime(),
            $this->getPrice($product, $domainConfig),
            $this->getHeurekaCategoryFullName($product, $domainConfig),
            $this->productDataBatchLoader->getProductParametersByName($product, $domainConfig),
            $this->productDataBatchLoader->getProductCpc($product, $domainConfig)
        );
    }
}