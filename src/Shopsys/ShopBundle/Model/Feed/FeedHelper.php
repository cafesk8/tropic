<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Feed;

use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Product\Product;

class FeedHelper
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param string|null $brandName
     * @return string
     */
    public function createProductName(Product $product, DomainConfig $domainConfig, ?string $brandName): string
    {
        $productName = $product->getName($domainConfig->getLocale());

        return sprintf('%s %s', $brandName, $productName);
    }
}
