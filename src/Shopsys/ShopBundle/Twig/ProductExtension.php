<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use Shopsys\FrameworkBundle\Model\Product\Exception\ProductNotFoundException;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Category\CategoryFacade;
use Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade;
use Shopsys\ShopBundle\Model\Product\ProductDistinguishingParameterValue;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Twig\TwigFunction;

class ProductExtension extends \Shopsys\FrameworkBundle\Twig\ProductExtension
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade
     */
    protected $productCachedAttributesFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Category\CategoryFacade $categoryFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     */
    public function __construct(
        CategoryFacade $categoryFacade,
        ProductCachedAttributesFacade $productCachedAttributesFacade,
        ProductFacade $productFacade
    ) {
        parent::__construct($categoryFacade, $productCachedAttributesFacade);

        $this->productFacade = $productFacade;
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
                'productDistinguishingParameterValue',
                [$this, 'getProductDistinguishingParameterValue']
            ),
            new TwigFunction(
                'getProductAdeptPrice',
                [$this, 'getProductAdeptPrice']
            ),
            new TwigFunction(
                'productParameters',
                [$this, 'getProductParameters']
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

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @return \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice|null
     */
    public function getProductAdeptPrice(Product $product): ?ProductPrice
    {
        return $this->productCachedAttributesFacade->getProductAdeptPrice($product);
    }

    /**
     * @param int $productId
     * @return string
     */
    public function getProductParameters(int $productId): string
    {
        try {
            $product = $this->productFacade->getById($productId);
        } catch (ProductNotFoundException $productNotFoundException) {
            return '';
        }

        $parametersForProduct = $this->productCachedAttributesFacade->getProductDistinguishingParameterValue($product);

        if ($parametersForProduct === null) {
            return '';
        }

        $parameters = [];

        if ($parametersForProduct->getFirstDistinguishingParameterValue() !== null) {
            $parameters[] = sprintf('%s: %s', $parametersForProduct->getFirstDistinguishingParameterName(), $parametersForProduct->getFirstDistinguishingParameterValue());
        }

        if ($parametersForProduct->getSecondDistinguishingParameterValue() !== null) {
            $parameters[] = sprintf('%s: %s', $parametersForProduct->getSecondDistinguishingParameterName(), $parametersForProduct->getSecondDistinguishingParameterValue());
        }

        if (count($parameters) === 0) {
            return '';
        }

        return sprintf('(%s)', implode(', ', $parameters));
    }
}
