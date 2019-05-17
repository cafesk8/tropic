<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ProductExtension extends AbstractExtension
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade
     */
    private $productCachedAttributesFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     */
    public function __construct(
        ProductCachedAttributesFacade $productCachedAttributesFacade
    ) {
        $this->productCachedAttributesFacade = $productCachedAttributesFacade;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'variantDistinguishingParameterValue',
                [$this, 'getVariantDistinguishingParameterValue']
            ),
            new TwigFunction(
                'mainVariantGroupDistinguishingParameterValue',
                [$this, 'findMainVariantGroupDistinguishingParameterValue']
            ),
        ];
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue|null
     */
    public function getVariantDistinguishingParameterValue(Product $product): ?ParameterValue
    {
        return $this->productCachedAttributesFacade->getProductDistinguishingParameterValue($product);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue|null
     */
    public function findMainVariantGroupDistinguishingParameterValue(Product $product): ?ParameterValue
    {
        return $this->productCachedAttributesFacade->findMainVariantGroupDistinguishingParameterValue($product);
    }
}
