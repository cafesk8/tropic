<?php

declare(strict_types=1);

namespace App\Form\Transformers;

use Shopsys\FrameworkBundle\Model\Product\Exception\ProductNotFoundException;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ProductGroupItemsTypeTransformer implements DataTransformerInterface
{
    /**
     * @var \App\Model\Product\ProductRepository
     */
    protected $productRepository;

    /**
     * @param \App\Model\Product\ProductRepository $productRepository
     */
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @param array|null $products
     * @return int[]
     */
    public function transform($products)
    {
        $productsIds = [];

        if (is_iterable($products)) {
            foreach ($products as $key => $product) {
                if (isset($product['item'])) {
                    $productsIds[$key] = $product['item']->getId();
                }
            }
        }

        return $productsIds;
    }

    /**
     * @param int[] $productsIds
     * @return \App\Model\Product\Product[]|null
     */
    public function reverseTransform($productsIds)
    {
        $products = [];

        if (is_array($productsIds)) {
            foreach ($productsIds as $key => $productId) {
                try {
                    $products[$key] = $this->productRepository->getById($productId);
                } catch (ProductNotFoundException $e) {
                    throw new TransformationFailedException('Product not found', null, $e);
                }
            }
        }

        return $products;
    }
}
