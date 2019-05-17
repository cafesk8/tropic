<?php

namespace Shopsys\ShopBundle\Model\Product;

use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue;
use Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade as BaseProductCachedAttributesFacade;

class ProductCachedAttributesFacade extends BaseProductCachedAttributesFacade
{
    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue|null
     */
    public function getProductDistinguishingParameterValue(Product $product): ?ParameterValue
    {
        $productParameterValues = $this->getProductParameterValues($product);

        foreach ($productParameterValues as $productParameterValue) {
            if ($productParameterValue->getParameter() === $product->getDistinguishingParameter()) {
                return $productParameterValue->getValue();
            }
        }

        return null;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValue|null
     */
    public function findMainVariantGroupDistinguishingParameterValue(Product $product): ?ParameterValue
    {
        $productParameterValues = $this->getProductParameterValues($product);

        foreach ($productParameterValues as $productParameterValue) {
            if ($productParameterValue->getParameter() === $product->getMainVariantGroup()->getDistinguishingParameter()) {
                return $productParameterValue->getValue();
            }
        }

        return null;
    }
}
