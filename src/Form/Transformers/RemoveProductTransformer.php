<?php

declare(strict_types=1);

namespace App\Form\Transformers;

use App\Model\Product\Product;
use Symfony\Component\Form\DataTransformerInterface;

class RemoveProductTransformer implements DataTransformerInterface
{
    /**
     * @var \App\Model\Product\Product
     */
    private $removeProduct;

    /**
     * @param \App\Model\Product\Product $removeProduct
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
