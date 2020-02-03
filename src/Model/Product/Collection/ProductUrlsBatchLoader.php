<?php

declare(strict_types=1);

namespace App\Model\Product\Collection;

use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Product\Collection\ProductUrlsBatchLoader as BaseProductUrlsBatchLoader;

/**
 * @method string getProductUrl(\App\Model\Product\Product $product, \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig)
 * @method string|null getProductImageUrl(\App\Model\Product\Product $product, \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig)
 * @method string getKey(\App\Model\Product\Product $product, \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig)
 */
class ProductUrlsBatchLoader extends BaseProductUrlsBatchLoader
{
    /**
     * @inheritDoc
     */
    public function loadForProducts(array $products, DomainConfig $domainConfig): void
    {
        $productsWithMainVariants = [];
        /** @var \App\Model\Product\Product $product */
        foreach ($products as $product) {
            $productsWithMainVariants[$product->getId()] = $product;
            if ($product->isVariant()) {
                $mainVariant = $product->getMainVariant();
                $productsWithMainVariants[$mainVariant->getId()] = $mainVariant;
            }
        }

        $productUrlsById = $this->productCollectionFacade->getAbsoluteUrlsIndexedByProductId($productsWithMainVariants, $domainConfig);
        $productImageUrlsById = $this->productCollectionFacade->getImagesUrlsIndexedByProductId($productsWithMainVariants, $domainConfig);

        foreach ($productsWithMainVariants as $product) {
            $key = $this->getKey($product, $domainConfig);
            $productId = $product->getId();

            if (array_key_exists($productId, $productUrlsById)) {
                $this->loadedProductUrls[$key] = $productUrlsById[$productId];
            }
            $this->loadedProductImageUrls[$key] = $productImageUrlsById[$productId];
        }
    }
}
