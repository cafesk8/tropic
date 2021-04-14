<?php

declare(strict_types=1);

namespace App\Model\LuigisBox;

use App\Component\Domain\DomainHelper;
use App\Component\Transfer\Logger\TransferLogger;
use App\Component\Transfer\Logger\TransferLoggerFactory;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;

class LuigisBoxCronModule implements SimpleCronModuleInterface
{
    public const TRANSFER_IDENTIFIER = 'export_luigis_box';

    private LuigisBoxFacade $luigisBoxFacade;

    private Domain $domain;

    private TransferLoggerFactory $loggerFactory;

    private TransferLogger $logger;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $loggerFactory
     * @param \App\Model\LuigisBox\LuigisBoxFacade $luigisBoxFacade
     */
    public function __construct(Domain $domain, TransferLoggerFactory $loggerFactory, LuigisBoxFacade $luigisBoxFacade)
    {
        $this->domain = $domain;
        $this->loggerFactory = $loggerFactory;
        $this->luigisBoxFacade = $luigisBoxFacade;
    }

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $this->loggerFactory->getTransferLoggerByIdentifier(self::TRANSFER_IDENTIFIER, $logger);
    }

    public function run()
    {
        $errorCount = 0;

        foreach ($this->domain->getAll() as $domainConfig) {
            if ($domainConfig->getId() === DomainHelper::ENGLISH_DOMAIN) {
                continue;
            }

            $models = $this->luigisBoxFacade->getExportableObjects($domainConfig);
            $this->logger->addInfo('Luigi\'s Box export starting for domain ' . $domainConfig->getId());

            try {
                $collectedExceptions = $this->luigisBoxFacade->sendToApi($models, $domainConfig);
                $errorCount += count($collectedExceptions);

                foreach ($collectedExceptions as $exception) {
                    $this->logger->addError($exception->getMessage(), $exception->getObjectUrls());
                }
            } catch (\Exception $exception) {
                $this->logger->addError($exception->getMessage(), $models);
                throw $exception;
            }

            $this->logger->addInfo('Luigi\'s Box export finished' . ($errorCount > 0 ? ' with errors' : ''));
        }
    }
}