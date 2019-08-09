<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductDistinguishingParameterValue;
use Twig\TwigFunction;

class ProductExtension extends \Shopsys\FrameworkBundle\Twig\ProductExtension
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade
     */
    protected $productCachedAttributesFacade;

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
                'productParameterValueByParameterId',
                [$this, 'getProductParameterValueByParameterId']
            ),
            new TwigFunction(
                'productDistinguishingParameterValue',
                [$this, 'getProductDistinguishingParameterValue']
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
     * @return \Shopsys\ShopBundle\Model\Product\ProductDistinguishingParameterValue
     */
    public function getProductDistinguishingParameterValue(Product $product): ProductDistinguishingParameterValue
    {
        return $this->productCachedAttributesFacade->getProductDistinguishingParameterValue($product);
    }

    /**
     * @inheritDoc
     */
    public function getProductDisplayName(Product $product)
    {
        $productDisplayName = parent::getProductDisplayName($product);

        return $this->addParameterValueToProductDisplayName($product, $productDisplayName);
    }

    /**
     * @inheritDoc
     */
    public function getProductListDisplayName(Product $product)
    {
        $productDisplayName = parent::getProductListDisplayName($product);

        return $this->addParameterValueToProductDisplayName($product, $productDisplayName);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param string $productListDisplayName
     * @return string
     */
    protected function addParameterValueToProductDisplayName(
        Product $product,
        string $productListDisplayName
    ): string {
        $productDistinguishingParameterValue = $this->productCachedAttributesFacade->getProductDistinguishingParameterValue($product);

        if ($productDistinguishingParameterValue->getFirstDistinguishingParameterValue() !== null) {
            $productListDisplayName .= ' - ' . $productDistinguishingParameterValue->getFirstDistinguishingParameterValue();
        }

        if ($productDistinguishingParameterValue->getSecondDistinguishingParameterValue() !== null) {
            $productListDisplayName .= ' - ' . $productDistinguishingParameterValue->getSecondDistinguishingParameterValue();
        }

        return $productListDisplayName;
    }
}
