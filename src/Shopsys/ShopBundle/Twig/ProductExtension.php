<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue;
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
                'distinguishingParameterValuesForProducts',
                [$this, 'findDistinguishingParameterValuesForProducts']
            ),
            new TwigFunction(
                'mainVariantGroupDistinguishingParameterValue',
                [$this, 'findMainVariantGroupDistinguishingParameterValue']
            ),
            new TwigFunction(
                'distinguishingProductParameterValueForVariant',
                [$this, 'findDistinguishingProductParameterValueForVariant']
            ),
            new TwigFunction(
                'productParameterValueByParameterId',
                [$this, 'getProductParameterValueByParameterId']
            ),
            new TwigFunction(
                'distinguishingProductParameterValueForProduct',
                [$this, 'getDistinguishingProductParameterValueForProduct']
            ),
        ];
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $products
     * @return string[]
     */
    public function findDistinguishingParameterValuesForProducts(array $products): array
    {
        return $this->productCachedAttributesFacade->findDistinguishingParameterValuesForProducts($products);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue|null
     */
    public function findMainVariantGroupDistinguishingParameterValue(Product $product): ?ParameterValue
    {
        return $this->productCachedAttributesFacade->findMainVariantGroupDistinguishingParameterValue($product);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue|null
     */
    public function findDistinguishingProductParameterValueForVariant(Product $product): ?ProductParameterValue
    {
        return $this->productCachedAttributesFacade->findDistinguishingProductParameterValueForVariant($product);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param int $parameterId
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue
     */
    public function getProductParameterValueByParameterId(Product $product, int $parameterId): ?ProductParameterValue
    {
        return $this->productCachedAttributesFacade->getProductParameterValueByParameterId($product, $parameterId);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return array
     */
    public function getDistinguishingProductParameterValueForProduct(Product $product): array
    {
        return $this->productCachedAttributesFacade->getDistinguishingParametersForProduct($product);
    }
}
