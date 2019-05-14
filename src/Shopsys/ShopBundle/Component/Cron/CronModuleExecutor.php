<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Cron;

class CronModuleExecutor extends \Shopsys\FrameworkBundle\Component\Cron\CronModuleExecutor
{
    /**
     * @inheritDoc
     */
    public function runModule($cronModuleService, $suspended)
    {
        // skip if needed

        // do something on START

        return parent::runModule($cronModuleService, $suspended);

        // do something on END
    }
}
