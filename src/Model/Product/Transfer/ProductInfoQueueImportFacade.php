<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade;

class ProductInfoQueueImportFacade
{
    /**
     * @var \App\Model\Product\Transfer\ProductInfoQueueImportRepository
     */
    private $productInfoQueueImportRepository;

    /**
     * @var \App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade
     */
    private $pohodaProductExportFacade;

    /**
     * @var \App\Component\Transfer\Logger\TransferLogger
     */
    private $logger;

    /**
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Model\Product\Transfer\ProductInfoQueueImportRepository $productInfoQueueImportRepository
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade $pohodaProductExportFacade
     */
    public function __construct(
        TransferLoggerFactory $transferLoggerFactory,
        ProductInfoQueueImportRepository $productInfoQueueImportRepository,
        PohodaProductExportFacade $pohodaProductExportFacade
    ) {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(ProductImportCronModule::TRANSFER_IDENTIFIER);
        $this->productInfoQueueImportRepository = $productInfoQueueImportRepository;
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
        $this->logger->addInfo('Spuštěn import do fronty produktů', ['transferLastStartAt' => $lastModificationDate]);
        $pohodaProductIds = $this->pohodaProductExportFacade->findPohodaProductIdsFromLastModificationDate($lastModificationDate);
        if (count($pohodaProductIds) === 0) {
            $this->logger->addInfo('Nejsou žádná data pro uložení do fronty');
        } else {
            $this->insertChangedPohodaProductIds($pohodaProductIds, $dateTimeBeforeTransferFromPohodaServer);
            $this->logger->addInfo('Celkem nových změněných produktů ve frontě', ['pohodaProductIdsCount' => count($pohodaProductIds)]);
        }

        $this->logger->persistTransferIssues();
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

    /**
     * @param array $pohodaProductIds
     */
    public function moveProductsToEndOfQueue(array $pohodaProductIds): void
    {
        $this->productInfoQueueImportRepository->moveProductsToEndOfQueue($pohodaProductIds);
    }
}
