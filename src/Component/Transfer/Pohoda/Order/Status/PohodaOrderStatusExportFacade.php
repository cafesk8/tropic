<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Order\Status;

use App\Component\Transfer\Logger\TransferLogger;
use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataException;
use App\Model\Order\Transfer\Status\OrderStatusImportCronModule;
use DateTime;

class PohodaOrderStatusExportFacade
{
    private PohodaOrderStatusExportRepository $pohodaOrderStatusExportRepository;

    private TransferLogger $logger;

    private PohodaOrderStatusDataValidator $pohodaOrderStatusDataValidator;

    /**
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Component\Transfer\Pohoda\Order\Status\PohodaOrderStatusExportRepository $pohodaOrderStatusExportRepository
     * @param \App\Component\Transfer\Pohoda\Order\Status\PohodaOrderStatusDataValidator $pohodaOrderStatusDataValidator
     */
    public function __construct(
        TransferLoggerFactory $transferLoggerFactory,
        PohodaOrderStatusExportRepository $pohodaOrderStatusExportRepository,
        PohodaOrderStatusDataValidator $pohodaOrderStatusDataValidator
    ) {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(OrderStatusImportCronModule::TRANSFER_IDENTIFIER);
        $this->pohodaOrderStatusExportRepository = $pohodaOrderStatusExportRepository;
        $this->pohodaOrderStatusDataValidator = $pohodaOrderStatusDataValidator;
    }

    /**
     * @param \DateTime|null $lastModificationDate
     * @return array
     */
    public function getPohodaOrderIdsFromLastModificationDate(?DateTime $lastModificationDate): array
    {
        return $this->pohodaOrderStatusExportRepository->getPohodaOrderIdsByLastUpdateTime($lastModificationDate);
    }

    /**
     * @param array $changedPohodaOrderIds
     * @return array
     */
    public function getPohodaOrderStatusesByPohodaIds(array $changedPohodaOrderIds): array
    {
        $pohodaOrderStatusesResult = $this->pohodaOrderStatusExportRepository->getByPohodaOrderIds(
            $changedPohodaOrderIds
        );

        return $this->getValidPohodaOrderStatuses($pohodaOrderStatusesResult);
    }

    /**
     * @param array $pohodaOrderStatusesData
     * @return array
     */
    private function getValidPohodaOrderStatuses(array $pohodaOrderStatusesData): array
    {
        $pohodaOrderStatuses = [];
        foreach ($pohodaOrderStatusesData as $pohodaOrderStatusData) {
            try {
                $this->pohodaOrderStatusDataValidator->validate($pohodaOrderStatusData);
            } catch (PohodaInvalidDataException $exc) {
                $this->logger->addError('Stav obejdnávky není validní a objednávka nebude aktualizována.', [
                    'pohodaId' => $pohodaOrderStatusData[PohodaOrderStatus::COL_POHODA_STATUS_ID],
                    'statusName' => $pohodaOrderStatusData[PohodaOrderStatus::COL_POHODA_STATUS_NAME],
                    'exceptionMessage' => $exc->getMessage(),
                ]);
                continue;
            }

            $pohodaOrderStatuses[$pohodaOrderStatusData[PohodaOrderStatus::COL_POHODA_ORDER_ID]] = new PohodaOrderStatus($pohodaOrderStatusData);
        }
        $this->logger->persistTransferIssues();

        return $pohodaOrderStatuses;
    }
}
