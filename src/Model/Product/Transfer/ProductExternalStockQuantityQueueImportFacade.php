<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Transfer\Logger\TransferLogger;
use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade;

class ProductExternalStockQuantityQueueImportFacade
{
    private TransferLogger $logger;

    private ProductExternalStockQuantityQueueImportRepository $productExternalStockQuantityQueueImportRepository;

    private PohodaProductExportFacade $pohodaProductExportFacade;

    /**
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Model\Product\Transfer\ProductExternalStockQuantityQueueImportRepository $productExternalStockQuantityQueueImportRepository
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade $pohodaProductExportFacade
     */
    public function __construct(
        TransferLoggerFactory $transferLoggerFactory,
        ProductExternalStockQuantityQueueImportRepository $productExternalStockQuantityQueueImportRepository,
        PohodaProductExportFacade $pohodaProductExportFacade
    ) {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(ProductExternalStockQuantityImportCronModule::TRANSFER_IDENTIFIER);
        $this->productExternalStockQuantityQueueImportRepository = $productExternalStockQuantityQueueImportRepository;
        $this->pohodaProductExportFacade = $pohodaProductExportFacade;
    }

    /**
     * @param \DateTime $dateTimeBeforeTransferFromPohodaServer
     * @param \DateTime|null $lastModificationDate
     */
    public function importDataToQueue(
        \DateTime $dateTimeBeforeTransferFromPohodaServer,
        ?\DateTime $lastModificationDate
    ): void {
        $this->logger->addInfo('Spuštěn import ID produktů do fronty skladových zásob', ['transferLastStartAt' => $lastModificationDate]);
        $pohodaProductIds = $this->pohodaProductExportFacade->getPohodaProductIdsByExternalStockLastUpdateTime($lastModificationDate);
        if (count($pohodaProductIds) === 0) {
            $this->logger->addInfo('Nejsou žádná data pro uložení do fronty skladových zásob');
        } else {
            $this->insertChangedPohodaProductIds($pohodaProductIds, $dateTimeBeforeTransferFromPohodaServer);
            $this->logger->addInfo('Celkem nových změněných produktů ve frontě skladových zásob', ['pohodaProductIdsCount' => count($pohodaProductIds)]);
        }

        $this->logger->persistTransferIssues();
    }

    /**
     * @param array $pohodaProductIds
     * @param \DateTime $pohodaTransferDateTime
     */
    public function insertChangedPohodaProductIds(array $pohodaProductIds, \DateTime $pohodaTransferDateTime): void
    {
        $this->productExternalStockQuantityQueueImportRepository->insertChangedPohodaProductIds($pohodaProductIds, $pohodaTransferDateTime);
    }

    /**
     * @param int $limit
     * @return int[]
     */
    public function getChangedPohodaProductIds(int $limit): array
    {
        return $this->productExternalStockQuantityQueueImportRepository->getChangedPohodaProductIds($limit);
    }

    /**
     * @param array $updatedPohodaProductIds
     */
    public function removeProductsFromQueue(array $updatedPohodaProductIds): void
    {
        $this->productExternalStockQuantityQueueImportRepository->removeProductsFromQueue($updatedPohodaProductIds);
    }

    /**
     * @return bool
     */
    public function isQueueEmpty(): bool
    {
        return $this->productExternalStockQuantityQueueImportRepository->isQueueEmpty();
    }
}
