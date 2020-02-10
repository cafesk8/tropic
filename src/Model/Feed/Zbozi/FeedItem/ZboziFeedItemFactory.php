<?php

declare(strict_types=1);

namespace App\Model\Feed\Zbozi\FeedItem;

use App\Model\Feed\FeedHelper;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Category\CategoryFacade;
use Shopsys\FrameworkBundle\Model\Product\Collection\ProductParametersBatchLoader;
use Shopsys\FrameworkBundle\Model\Product\Collection\ProductUrlsBatchLoader;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForCustomerUser;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\ProductFeed\ZboziBundle\Model\FeedItem\ZboziFeedItem;
use Shopsys\ProductFeed\ZboziBundle\Model\FeedItem\ZboziFeedItemFactory as BaseZboziFeedItemFactory;
use Shopsys\ProductFeed\ZboziBundle\Model\Product\ZboziProductDomain;

class ZboziFeedItemFactory extends BaseZboziFeedItemFactory
{
    /**
     * @var \App\Model\Feed\FeedHelper
     */
    private $feedHelper;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForCustomerUser $productPriceCalculationForUser
     * @param \App\Model\Product\Collection\ProductUrlsBatchLoader $productUrlsBatchLoader
     * @param \Shopsys\FrameworkBundle\Model\Product\Collection\ProductParametersBatchLoader $productParametersBatchLoader
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Model\Feed\FeedHelper $feedHelper
     */
    public function __construct(
        ProductPriceCalculationForCustomerUser $productPriceCalculationForUser,
        ProductUrlsBatchLoader $productUrlsBatchLoader,
        ProductParametersBatchLoader $productParametersBatchLoader,
        CategoryFacade $categoryFacade,
        FeedHelper $feedHelper
    ) {
        parent::__construct($productPriceCalculationForUser, $productUrlsBatchLoader, $productParametersBatchLoader, $categoryFacade);
        $this->feedHelper = $feedHelper;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\ProductFeed\ZboziBundle\Model\Product\ZboziProductDomain|null $zboziProductDomain
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \Shopsys\ProductFeed\ZboziBundle\Model\FeedItem\ZboziFeedItem
     */
    public function create(Product $product, ?ZboziProductDomain $zboziProductDomain, DomainConfig $domainConfig): ZboziFeedItem
    {
        $productForDescription = $product->isVariant() === true ? $product->getMainVariant() : $product;

        $mainVariantId = $product->isVariant() ? $product->getMainVariant()->getId() : null;
        $cpc = $zboziProductDomain !== null ? $zboziProductDomain->getCpc() : null;
        $cpcSearch = $zboziProductDomain !== null ? $zboziProductDomain->getCpcSearch() : null;

        $brandName = $this->getBrandName($product);
        $productName = $this->feedHelper->createProductName($product, $domainConfig, $brandName);

        return new ZboziFeedItem(
            $product->getId(),
            $mainVariantId,
            $productName,
            $productForDescription->getDescription($domainConfig->getId()),
            $this->productUrlsBatchLoader->getProductUrl($product, $domainConfig),
            $this->productUrlsBatchLoader->getProductImageUrl($product, $domainConfig),
            $this->getBrandName($product),
            $product->getEan(),
            $product->getPartno(),
            $product->getCalculatedAvailability()->getDispatchTime(),
            $this->getPrice($product, $domainConfig),
            $this->getPathToMainCategory($product, $domainConfig),
            $this->productParametersBatchLoader->getProductParametersByName($product, $domainConfig),
            $cpc,
            $cpcSearch
        );
    }
}
