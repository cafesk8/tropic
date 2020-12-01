<?php

declare(strict_types=1);

namespace App\Component\Cron;

use Shopsys\FrameworkBundle\Component\Cron\CronModule;
use Shopsys\FrameworkBundle\Component\Cron\CronModuleFacade as BaseCronModuleFacade;

/**
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 */
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

    /**
     * @param string $serviceId
     * @return bool
     */
    public function isCronModuleRunning(string $serviceId): bool
    {
        $cronModule = $this->cronModuleRepository->getCronModuleByServiceId($serviceId);
        // We need to refresh the entity here to be sure the current state is fetched from the database.
        $this->em->refresh($cronModule);

        return $cronModule->getStatus() === CronModule::CRON_STATUS_RUNNING;
    }
}
