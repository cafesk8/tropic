<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Model\Product\Exception\CreatingVariantWithoutMainVariantException;
use Shopsys\FrameworkBundle\Model\Product\Exception\ProductIsNotVariantException;

class ProductVariantTropicFacade
{
    public const VARIANT_ID_SEPARATOR = '*';

    /**
     * @var \App\Model\Product\ProductRepository
     */
    protected ProductRepository $productRepository;

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
            try {
                $product->getMainVariant();
                $product->unsetMainVariant();
            } catch (ProductIsNotVariantException $exception) {
                $product->setVariantType(Product::VARIANT_TYPE_NONE);
            }
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

        $mainVariant = $this->productRepository->findMainVariantByVariantId(Product::getMainVariantVariantIdFromVariantVariantId($variantId));

        if ($mainVariant === null) {
            $mainVariant = $this->productRepository->findMainVariantByVariantId(trim(Product::getMainVariantVariantIdFromVariantVariantId($variantId)));
        }
        if ($mainVariant === null) {
            $mainVariant = $this->productRepository->findMainVariantByVariantId(Product::getMainVariantVariantIdFromVariantVariantId($variantId) . ' ');
        }

        return $mainVariant;
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
     * @param string $mainVariantId
     * @return \App\Model\Product\Product[]
     */
    public function getVariantsByMainVariantId(string $mainVariantId): array
    {
        return $this->productRepository->getVariantsByMainVariantId($mainVariantId);
    }
}
