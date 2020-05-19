<?php

declare(strict_types=1);

namespace App\Model\Order\Transfer;

use App\Component\Transfer\AbstractTransferCronModule;
use App\Component\Transfer\TransferCronModuleDependency;

class OrderExportCronModule extends AbstractTransferCronModule
{
    public const TRANSFER_IDENTIFIER = 'export_orders';

    /**
     * @var \App\Model\Order\Transfer\OrderExportFacade
     */
    private $orderExportFacade;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Model\Order\Transfer\OrderExportFacade $orderExportFacade
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        OrderExportFacade $orderExportFacade
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->orderExportFacade = $orderExportFacade;
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
        $this->orderExportFacade->processExport();

        return false;
    }
}
