<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Collection;

use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Product\Collection\ProductUrlsBatchLoader as BaseProductUrlsBatchLoader;

class ProductUrlsBatchLoader extends BaseProductUrlsBatchLoader
{
    /**
     * @inheritDoc
     */
    public function loadForProducts(array $products, DomainConfig $domainConfig): void
    {
        $productsWithMainVariants = [];
        /** @var \Shopsys\ShopBundle\Model\Product\Product $product */
        foreach ($products as $product) {
            $productsWithMainVariants[$product->getId()] = $product;
            if ($product->isVariant()) {
                $mainVariant = $product->getMainVariant();
                $productsWithMainVariants[$mainVariant->getId()] = $mainVariant;
            }
        }

        parent::loadForProducts($productsWithMainVariants, $domainConfig);
    }
}
