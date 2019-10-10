<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Feed;

use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade;

class FeedHelper
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade
     */
    private $productCachedAttributesFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     */
    public function __construct(ProductCachedAttributesFacade $productCachedAttributesFacade)
    {
        $this->productCachedAttributesFacade = $productCachedAttributesFacade;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param string|null $brandName
     * @return string
     */
    public function createProductName(Product $product, DomainConfig $domainConfig, ?string $brandName): string
    {
        $productName = $product->getName($domainConfig->getLocale());

        if ($product->getVariantType() === Product::VARIANT_TYPE_NONE) {
            return $productName;
        }

        $productDistinguishingParameterValue = $this->productCachedAttributesFacade->getProductDistinguishingParameterValue(
            $product,
            $domainConfig->getLocale()
        );

        $colorValue = $productDistinguishingParameterValue->getFirstDistinguishingParameterValue();
        $sizeValue = $productDistinguishingParameterValue->getSecondDistinguishingParameterValue();

        return sprintf('%s %s %s %s', $brandName, $productName, $colorValue, $sizeValue);
    }
}
