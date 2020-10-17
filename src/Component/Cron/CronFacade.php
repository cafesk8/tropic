<?php

declare(strict_types=1);

namespace App\Component\Cron;

use Shopsys\FrameworkBundle\Component\Cron\Config\CronModuleConfig;
use Shopsys\FrameworkBundle\Component\Cron\CronFacade as BaseCronFacade;
use Shopsys\FrameworkBundle\Component\Cron\CronModuleExecutor;
use Swift_TransportException;
use Throwable;

/**
 * @property \App\Component\Cron\Config\CronConfig $cronConfig
 * @property \App\Component\Cron\CronModuleFacade $cronModuleFacade
 * @property \App\Component\Cron\CronModuleExecutor $cronModuleExecutor
 * @method __construct(\Symfony\Bridge\Monolog\Logger $logger, \App\Component\Cron\Config\CronConfig $cronConfig, \App\Component\Cron\CronModuleFacade $cronModuleFacade, \Shopsys\FrameworkBundle\Model\Mail\Mailer $mailer, \App\Component\Cron\CronModuleExecutor $cronModuleExecutor)
 */
class CronFacade extends BaseCronFacade
{
    /**
     * Extended logging - when a cron module fails on error, the exception is added to logger context
     * @see https://github.com/shopsys/shopsys/pull/2079
     *
     * @param \Shopsys\FrameworkBundle\Component\Cron\Config\CronModuleConfig $cronModuleConfig
     */
    protected function runSingleModule(CronModuleConfig $cronModuleConfig)
    {
        if ($this->cronModuleFacade->isModuleDisabled($cronModuleConfig) === true) {
            return;
        }

        $this->logger->addInfo('Start of ' . $cronModuleConfig->getServiceId());
        $cronModuleService = $cronModuleConfig->getService();
        $cronModuleService->setLogger($this->logger);
        $this->cronModuleFacade->markCronAsStarted($cronModuleConfig);

        try {
            $status = $this->cronModuleExecutor->runModule(
                $cronModuleService,
                $this->cronModuleFacade->isModuleSuspended($cronModuleConfig)
            );
        } catch (Throwable $throwable) {
            $this->cronModuleFacade->markCronAsFailed($cronModuleConfig);
            $this->logger->addError('End of ' . $cronModuleConfig->getServiceId() . ' because of error', [
                'throwable' => $throwable,
            ]);
            throw $throwable;
        }

        $this->cronModuleFacade->markCronAsEnded($cronModuleConfig);

        try {
            $this->mailer->flushSpoolQueue();
        } catch (Swift_TransportException $exception) {
            $this->logger->addError('An exception occurred while flushing email queue. Message: "{message}"', ['exception' => $exception, 'message' => $exception->getMessage()]);
        }

        if ($status === CronModuleExecutor::RUN_STATUS_OK) {
            $this->cronModuleFacade->unscheduleModule($cronModuleConfig);
            $this->logger->addInfo('End of ' . $cronModuleConfig->getServiceId());
        } elseif ($status === CronModuleExecutor::RUN_STATUS_SUSPENDED) {
            $this->cronModuleFacade->suspendModule($cronModuleConfig);
            $this->logger->addInfo('Suspend ' . $cronModuleConfig->getServiceId());
        } elseif ($status === CronModuleExecutor::RUN_STATUS_TIMEOUT) {
            $this->logger->info('Cron reached timeout.');
        }
    }
}
