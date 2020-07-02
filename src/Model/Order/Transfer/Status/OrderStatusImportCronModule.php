<?php

declare(strict_types=1);

namespace App\Model\Order\Transfer\Status;

use App\Component\Transfer\AbstractTransferCronModule;
use App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager;
use App\Component\Transfer\TransferCronModuleDependency;

class OrderStatusImportCronModule extends AbstractTransferCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_order_statuses';

    private PohodaEntityManager $pohodaEntityManager;

    private OrderStatusQueueImportFacade $orderStatusQueueImportFacade;

    private OrderStatusImportFacade $orderStatusImportFacade;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager $pohodaEntityManager
     * @param \App\Model\Order\Transfer\Status\OrderStatusQueueImportFacade $orderStatusQueueImportFacade
     * @param \App\Model\Order\Transfer\Status\OrderStatusImportFacade $orderStatusImportFacade
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        PohodaEntityManager $pohodaEntityManager,
        OrderStatusQueueImportFacade $orderStatusQueueImportFacade,
        OrderStatusImportFacade $orderStatusImportFacade
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->pohodaEntityManager = $pohodaEntityManager;
        $this->orderStatusQueueImportFacade = $orderStatusQueueImportFacade;
        $this->orderStatusImportFacade = $orderStatusImportFacade;
    }

    /**
     * @inheritDoc
     */
    protected function getTransferIdentifier(): string
    {
        return self::TRANSFER_IDENTIFIER;
    }

    /**
     * @inheritDoc
     */
    protected function runTransfer(): bool
    {
        $transfer = $this->transferFacade->getByIdentifier(self::TRANSFER_IDENTIFIER);
        $dateTimeBeforeTransferFromPohodaServer = $this->pohodaEntityManager->getCurrentDateTimeFromPohodaDatabase();
        $this->orderStatusQueueImportFacade->importDataToQueue($dateTimeBeforeTransferFromPohodaServer, $transfer->getLastStartAt());

        return false;
    }
}
