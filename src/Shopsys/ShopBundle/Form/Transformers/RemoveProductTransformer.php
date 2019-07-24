<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Transformers;

use Shopsys\ShopBundle\Model\Product\Product;
use Symfony\Component\Form\DataTransformerInterface;

class RemoveProductTransformer implements DataTransformerInterface
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\Product
     */
    private $removeProduct;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $removeProduct
     */
    public function __construct(Product $removeProduct)
    {
        $this->removeProduct = $removeProduct;
    }

    /**
     * @param mixed $values
     * @return mixed
     */
    public function transform($values)
    {
        foreach ($values as $index => $product) {
            if ($product === $this->removeProduct) {
                unset($values[$index]);
            }
        }

        return $values;
    }

    /**
     * @param array|null $array
     * @return array|null
     */
    public function reverseTransform($array)
    {
        return $array;
    }
}
