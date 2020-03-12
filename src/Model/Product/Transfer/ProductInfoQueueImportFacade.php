<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

class ProductInfoQueueImportFacade
{
    /**
     * @var \App\Model\Product\Transfer\ProductInfoQueueImportRepository
     */
    private $productInfoQueueImportRepository;

    /**
     * @param \App\Model\Product\Transfer\ProductInfoQueueImportRepository $productInfoQueueImportRepository
     */
    public function __construct(ProductInfoQueueImportRepository $productInfoQueueImportRepository)
    {
        $this->productInfoQueueImportRepository = $productInfoQueueImportRepository;
    }

    /**
     * @param array $pohodaProductIds
     * @param \DateTime $pohodaTransferDateTime
     */
    public function insertChangedPohodaProductIds(array $pohodaProductIds, \DateTime $pohodaTransferDateTime): void
    {
        $this->productInfoQueueImportRepository->insertChangedPohodaProductIds($pohodaProductIds, $pohodaTransferDateTime);
    }

    /**
     * @param int $limit
     * @return int[]
     */
    public function findChangedPohodaProductIds(int $limit): array
    {
        return $this->productInfoQueueImportRepository->findChangedPohodaProductIds($limit);
    }

    /**
     * @param array $updatedPohodaProductIds
     */
    public function removeProductsFromQueue(array $updatedPohodaProductIds): void
    {
        $this->productInfoQueueImportRepository->removeUpdatedProducts($updatedPohodaProductIds);
    }

    /**
     * @return bool
     */
    public function isQueueEmpty(): bool
    {
        return $this->productInfoQueueImportRepository->isQueueEmpty();
    }
}
