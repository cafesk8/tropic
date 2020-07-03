<?php

declare(strict_types=1);

namespace App\Model\Order\Transfer\Status;

use App\Component\Transfer\Logger\TransferLogger;
use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Component\Transfer\Pohoda\Order\Status\PohodaOrderStatusExportFacade;

class OrderStatusQueueImportFacade
{
    private TransferLogger $logger;

    private OrderStatusQueueImportRepository $orderStatusQueueImportRepository;

    private PohodaOrderStatusExportFacade $pohodaOrderStatusExportFacade;

    /**
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Model\Order\Transfer\Status\OrderStatusQueueImportRepository $orderStatusQueueImportRepository
     * @param \App\Component\Transfer\Pohoda\Order\Status\PohodaOrderStatusExportFacade $pohodaOrderStatusExportFacade
     */
    public function __construct(
        TransferLoggerFactory $transferLoggerFactory,
        OrderStatusQueueImportRepository $orderStatusQueueImportRepository,
        PohodaOrderStatusExportFacade $pohodaOrderStatusExportFacade
    ) {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(OrderStatusImportCronModule::TRANSFER_IDENTIFIER);
        $this->orderStatusQueueImportRepository = $orderStatusQueueImportRepository;
        $this->pohodaOrderStatusExportFacade = $pohodaOrderStatusExportFacade;
    }

    /**
     * @param \DateTime $dateTimeBeforeTransferFromPohodaServer
     * @param \DateTime|null $lastModificationDate
     */
    public function importDataToQueue(
        \DateTime $dateTimeBeforeTransferFromPohodaServer,
        ?\DateTime $lastModificationDate
    ): void {
        $this->logger->addInfo('Spuštěn import do fronty stavů objednávek', ['transferLastStartAt' => $lastModificationDate]);
        $pohodaOrderIds = $this->pohodaOrderStatusExportFacade->getPohodaOrderIdsFromLastModificationDate($lastModificationDate);
        if (count($pohodaOrderIds) === 0) {
            $this->logger->addInfo('Nejsou žádná data pro uložení do fronty');
        } else {
            $this->orderStatusQueueImportRepository->insertChangedPohodaOrderIds($pohodaOrderIds, $dateTimeBeforeTransferFromPohodaServer);
            $this->logger->addInfo('Vložeí nových objednávek do fronty', ['pohodaOrdersIdsCount' => count($pohodaOrderIds)]);
        }

        $this->logger->persistTransferIssues();
    }

    /**
     * @param int $limit
     * @return array
     */
    public function getChangedPohodaOrderIds(int $limit): array
    {
        return $this->orderStatusQueueImportRepository->getChangedPohodaOrderIds($limit);
    }

    /**
     * @param array $updatedPohodaOrderIds
     */
    public function removeOrdersFromQueue(array $updatedPohodaOrderIds): void
    {
        $this->orderStatusQueueImportRepository->removeOrdersFromQueue($updatedPohodaOrderIds);
    }

    /**
     * @return bool
     */
    public function isQueueEmpty(): bool
    {
        return $this->orderStatusQueueImportRepository->isQueueEmpty();
    }
}
