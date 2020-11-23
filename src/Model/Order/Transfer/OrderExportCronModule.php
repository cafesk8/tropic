<?php

declare(strict_types=1);

namespace App\Model\Order\Transfer;

use App\Component\Cron\CronModuleFacade;
use App\Component\Transfer\AbstractTransferCronModule;
use App\Component\Transfer\TransferCronModuleDependency;
use App\Model\Product\Transfer\ProductImageImportCronModule;

class OrderExportCronModule extends AbstractTransferCronModule
{
    public const TRANSFER_IDENTIFIER = 'export_orders';
    public const MAX_ATTEMPTS_TO_AVOID_IMAGES_CONFLICT = 10;

    private OrderExportFacade $orderExportFacade;

    private CronModuleFacade $cronModuleFacade;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Model\Order\Transfer\OrderExportFacade $orderExportFacade
     * @param \App\Component\Cron\CronModuleFacade $cronModuleFacade
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        OrderExportFacade $orderExportFacade,
        CronModuleFacade $cronModuleFacade
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->orderExportFacade = $orderExportFacade;
        $this->cronModuleFacade = $cronModuleFacade;
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
     * Also, the module must not start when images import is in progress, otherwise it causes deadlocks in Pohoda
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

        $attempts = 0;
        $imageCronIsRunning = null;
        while ($attempts < self::MAX_ATTEMPTS_TO_AVOID_IMAGES_CONFLICT) {
            $imageCronIsRunning = $this->cronModuleFacade->isCronModuleRunning(ProductImageImportCronModule::class);
            if (!$imageCronIsRunning) {
                return true;
            }
            $attempts++;
            sleep(1);
        }
        $this->logger->addInfo('Orders export suspended because the images import from Pohoda is in progress');
        $this->logger->persistTransferIssues();
        return false;
    }
}
