<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Cron;

use Shopsys\FrameworkBundle\Component\Cron\CronModuleFacade as BaseCronModuleFacade;

class CronModuleFacade extends BaseCronModuleFacade
{
    /**
     * @param string $serviceId
     */
    public function scheduleModuleByServiceId(string $serviceId): void
    {
        $cronModule = $this->cronModuleRepository->getCronModuleByServiceId($serviceId);
        $cronModule->schedule();
        $this->em->flush($cronModule);
    }
}
