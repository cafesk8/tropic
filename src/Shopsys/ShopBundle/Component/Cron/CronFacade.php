<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Cron;

class CronFacade extends \Shopsys\FrameworkBundle\Component\Cron\CronFacade
{
    /**
     * @inheritDoc
     */
    public function runScheduledModules()
    {
        $cronModuleExecutor = new CronModuleExecutor(self::TIMEOUT_SECONDS);

        $cronModuleConfigs = $this->cronConfig->getAllCronModuleConfigs();

        $scheduledCronModuleConfigs = $this->cronModuleFacade->getOnlyScheduledCronModuleConfigs($cronModuleConfigs);
        $this->runModules($cronModuleExecutor, $scheduledCronModuleConfigs);
    }

    /**
     * @inheritDoc
     */
    public function runScheduledModulesForInstance(string $instanceName): void
    {
        $cronModuleExecutor = new CronModuleExecutor(self::TIMEOUT_SECONDS);

        $cronModuleConfigs = $this->cronConfig->getCronModuleConfigsForInstance($instanceName);

        $scheduledCronModuleConfigs = $this->cronModuleFacade->getOnlyScheduledCronModuleConfigs($cronModuleConfigs);
        $this->runModulesForInstance($cronModuleExecutor, $scheduledCronModuleConfigs, $instanceName);
    }

    /**
     * @inheritDoc
     */
    public function runModuleByServiceId($serviceId)
    {
        $cronModuleConfig = $this->cronConfig->getCronModuleConfigByServiceId($serviceId);

        $cronModuleExecutor = new CronModuleExecutor(self::TIMEOUT_SECONDS);
        $this->runModule($cronModuleExecutor, $cronModuleConfig);
    }
}
