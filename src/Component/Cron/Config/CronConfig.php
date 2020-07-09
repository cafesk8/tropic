<?php

declare(strict_types=1);

namespace App\Component\Cron\Config;

use Shopsys\FrameworkBundle\Component\Cron\Config\CronConfig as BaseCronConfig;
use Shopsys\Plugin\Cron\IteratedCronModuleInterface;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;

class CronConfig extends BaseCronConfig
{
    /**
     * Changes CronModuleConfig to its overriden version
     *
     * @param \Shopsys\Plugin\Cron\SimpleCronModuleInterface|\Shopsys\Plugin\Cron\IteratedCronModuleInterface|mixed $service
     * @param string $serviceId
     * @param string $timeHours
     * @param string $timeMinutes
     * @param string $instanceName
     * @param string|null $readableName
     */
    public function registerCronModuleInstance($service, string $serviceId, string $timeHours, string $timeMinutes, string $instanceName, ?string $readableName = null): void
    {
        if (!$service instanceof SimpleCronModuleInterface && !$service instanceof IteratedCronModuleInterface) {
            throw new \Shopsys\FrameworkBundle\Component\Cron\Exception\InvalidCronModuleException($serviceId);
        }
        $this->cronTimeResolver->validateTimeString($timeHours, 23, 1);
        $this->cronTimeResolver->validateTimeString($timeMinutes, 55, 5);

        $cronModuleConfig = new CronModuleConfig($service, $serviceId, $timeHours, $timeMinutes, $readableName);
        $cronModuleConfig->assignToInstance($instanceName);

        $this->cronModuleConfigs[] = $cronModuleConfig;
    }
}
