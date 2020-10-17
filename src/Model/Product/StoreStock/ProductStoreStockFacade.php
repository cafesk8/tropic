<?php

declare(strict_types=1);

namespace App\Model\Product\StoreStock;

class ProductStoreStockFacade
{
    private ProductStoreStockRepository $productStoreStockRepository;

    /**
     * @param \App\Model\Product\StoreStock\ProductStoreStockRepository $productStoreStockRepository
     */
    public function __construct(ProductStoreStockRepository $productStoreStockRepository)
    {
        $this->productStoreStockRepository = $productStoreStockRepository;
    }

    /**
     * @param int $productId
     * @param int $storeId
     * @param int $quantity
     */
    public function manualInsertStoreStock(int $productId, int $storeId, int $quantity): void
    {
        $this->productStoreStockRepository->manualInsertStoreStock($productId, $storeId, $quantity);
    }

    /**
     * @param array $productIds
     * @param int $storeId
     * @return array
     */
    public function getProductStockQuantities(array $productIds, int $storeId): array
    {
        return $this->productStoreStockRepository->getProductStockQuantities($productIds, $storeId);
    }
}
