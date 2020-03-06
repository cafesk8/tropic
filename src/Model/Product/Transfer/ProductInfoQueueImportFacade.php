<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

class ProductInfoQueueImportFacade
{
    /**
     * @var \App\Model\Product\Transfer\ProductInfoQueueImportRepository
     */
    private $basicInfoQueueImportRepository;

    /**
     * @param \App\Model\Product\Transfer\ProductInfoQueueImportRepository $basicInfoQueueImportRepository
     */
    public function __construct(ProductInfoQueueImportRepository $basicInfoQueueImportRepository)
    {
        $this->basicInfoQueueImportRepository = $basicInfoQueueImportRepository;
    }

    /**
     * @param array $pohodaProductIds
     * @param \DateTime $pohodaTransferDateTime
     */
    public function insertChangedPohodaProductIds(array $pohodaProductIds, \DateTime $pohodaTransferDateTime): void
    {
        $this->basicInfoQueueImportRepository->insertChangedPohodaProductIds($pohodaProductIds, $pohodaTransferDateTime);
    }

    /**
     * @param int $limit
     * @return int[]
     */
    public function findChangedPohodaProductIds(int $limit): array
    {
        return $this->basicInfoQueueImportRepository->findChangedPohodaProductIds($limit);
    }

    /**
     * @param array $updatedPohodaProductIds
     */
    public function removeProductsFromQueue(array $updatedPohodaProductIds): void
    {
        $this->basicInfoQueueImportRepository->removeUpdatedProducts($updatedPohodaProductIds);
    }
}
