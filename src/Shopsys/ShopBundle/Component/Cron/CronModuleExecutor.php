<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Cron;

use Shopsys\ShopBundle\Component\Transfer\TransferIteratedCronModuleInterface;

class CronModuleExecutor extends \Shopsys\FrameworkBundle\Component\Cron\CronModuleExecutor
{
    /**
     * @inheritDoc
     */
    public function runModule($cronModuleService, $suspended)
    {
        if ($cronModuleService instanceof TransferIteratedCronModuleInterface && $cronModuleService->isSkipped() === true) {
            return parent::RUN_STATUS_OK;
        }

        if ($cronModuleService instanceof TransferIteratedCronModuleInterface) {
            $cronModuleService->start();
        }

        $runModuleStatus = parent::runModule($cronModuleService, $suspended);

        if ($cronModuleService instanceof TransferIteratedCronModuleInterface) {
            $cronModuleService->end();
        }

        return $runModuleStatus;
    }
}
