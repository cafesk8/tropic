<?php

declare(strict_types=1);

namespace App\Model\Order\Transfer;

use App\Component\Transfer\AbstractTransferCronModule;
use App\Component\Transfer\TransferCronModuleDependency;

class OrderExportCronModule extends AbstractTransferCronModule
{
    public const TRANSFER_IDENTIFIER = 'export_orders';

    private OrderExportFacade $orderExportFacade;

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
        if (!$this->shouldRun()) {
            return false;
        }

        $this->orderExportFacade->processExport();

        return false;
    }

    /**
     * Customers' orders shouldn't be sent to Pohoda from 7:30 to 8:30 because they manage suppliers' orders during this time
     *
     * @return bool
     */
    private function shouldRun(): bool
    {
        $defaultTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/Prague');
        $currentHour = (int)date('G');
        $currentMinute = (int)date('i');
        date_default_timezone_set($defaultTimezone);

        if (($currentHour === 7 && $currentMinute >= 30) || ($currentHour === 8 && $currentMinute < 30)) {
            return false;
        }

        return true;
    }
}
