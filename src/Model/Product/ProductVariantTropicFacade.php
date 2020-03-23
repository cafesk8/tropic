<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Model\Product\Exception\CreatingVariantWithoutMainVariantException;

class ProductVariantTropicFacade
{
    public const VARIANT_ID_SEPARATOR = '/';

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
     * @param \App\Model\Product\Product $product
     * @param string|null $variantId
     */
    public function refreshVariantStatus(Product $product, ?string $variantId): void
    {
        if ($variantId === null) {
            $product->setVariantType(Product::VARIANT_TYPE_NONE);
        } elseif ($this->isMainVariant($variantId)) {
            $product->setVariantType(Product::VARIANT_TYPE_MAIN);
        } else {
            $mainVariant = $this->findMainVariantByVariantId($variantId);
            if ($mainVariant !== null) {
                $mainVariant->addVariant($product);
                $product->setVariantType(Product::VARIANT_TYPE_VARIANT);
            } else {
                throw new CreatingVariantWithoutMainVariantException($variantId);
            }
        }
    }

    /**
     * @param string|null $variantId
     * @return \App\Model\Product\Product|null
     */
    public function findMainVariantByVariantId(?string $variantId): ?Product
    {
        if ($variantId === null || $this->isMainVariant($variantId)) {
            return null;
        }

        return $this->productRepository->findMainVariantByVariantId($this->getMainVariantVariantIdFromVariantVariantId($variantId));
    }

    /**
     * @param string $variantId
     * @return \App\Model\Product\Product|null
     */
    public function findByVariantId(string $variantId): ?Product
    {
        return $this->productRepository->findByVariantId($variantId);
    }

    /**
     * @param string|null $productVariantId
     * @return bool
     */
    public function isVariant(?string $productVariantId): bool
    {
        return $productVariantId !== null && strpos($productVariantId, self::VARIANT_ID_SEPARATOR) !== false;
    }

    /**
     * @param string|null $productVariantId
     * @return bool
     */
    public function isMainVariant(?string $productVariantId): bool
    {
        return $productVariantId !== null && strpos($productVariantId, self::VARIANT_ID_SEPARATOR) === false;
    }

    /**
     * @param string $variantId
     * @return string
     */
    private function getMainVariantVariantIdFromVariantVariantId(string $variantId): string
    {
        return substr($variantId, 0, strpos($variantId, self::VARIANT_ID_SEPARATOR));
    }
}
