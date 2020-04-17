<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Transfer\Logger\TransferLogger;
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
     * @param \App\Model\Product\Transfer\ProductInfoQueueImportRepository $productInfoQueueImportRepository
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade $pohodaProductExportFacade
     */
    public function __construct(
        ProductInfoQueueImportRepository $productInfoQueueImportRepository,
        PohodaProductExportFacade $pohodaProductExportFacade
    ) {
        $this->productInfoQueueImportRepository = $productInfoQueueImportRepository;
        $this->pohodaProductExportFacade = $pohodaProductExportFacade;
    }

    /**
     * @param \App\Component\Transfer\Logger\TransferLogger $transferLogger
     * @param \DateTime $dateTimeBeforeTransferFromPohodaServer
     * @param \DateTime|null $lastModificationDate
     */
    public function importDataToQueue(
        TransferLogger $transferLogger,
        \DateTime $dateTimeBeforeTransferFromPohodaServer,
        ?\DateTime $lastModificationDate
    ): void {
        $transferLogger->addInfo('Spuštěn import do fronty produktů', ['transferLastStartAt' => $lastModificationDate]);
        $pohodaProductIds = $this->pohodaProductExportFacade->findPohodaProductIdsFromLastModificationDate($lastModificationDate);
        if (count($pohodaProductIds) === 0) {
            $transferLogger->addInfo('Nejsou žádná data pro uložení do fronty');
        } else {
            $this->insertChangedPohodaProductIds($pohodaProductIds, $dateTimeBeforeTransferFromPohodaServer);
            $transferLogger->addInfo('Celkem nových změněných produktů ve frontě', ['pohodaProductIdsCount' => count($pohodaProductIds)]);
        }
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
