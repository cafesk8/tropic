<?php

declare(strict_types=1);

namespace App\Component\Transfer;

use DateTime;
use Shopsys\Plugin\Cron\IteratedCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;

abstract class AbstractTransferCronModule implements IteratedCronModuleInterface, TransferIteratedCronModuleInterface
{
    /**
     * @var \App\Component\Transfer\Logger\TransferLogger
     */
    protected $logger;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \App\Model\Transfer\TransferFacade
     */
    protected $transferFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Doctrine\SqlLoggerFacade
     */
    private $sqlLoggerFacade;

    /**
     * @var \Symfony\Component\Validator\Validator\ValidatorInterface
     */
    protected $validator;

    /**
     * @var \App\Component\Transfer\Logger\TransferLoggerFactory
     */
    private $transferLoggerFactory;

    /**
     * @var \DateTime
     */
    private $startAt;

    /**
     * @var \App\Model\Transfer\Issue\TransferIssueFacade
     */
    protected $transferIssueFacade;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     */
    public function __construct(TransferCronModuleDependency $transferCronModuleDependency)
    {
        $this->em = $transferCronModuleDependency->getEntityManager();
        $this->transferFacade = $transferCronModuleDependency->getTransferFacade();
        $this->sqlLoggerFacade = $transferCronModuleDependency->getSqlLoggerFacade();
        $this->validator = $transferCronModuleDependency->getValidator();
        $this->transferLoggerFactory = $transferCronModuleDependency->getTransferLoggerFactory();
        $this->transferIssueFacade = $transferCronModuleDependency->getTransferIssueFacade();
    }

    /**
     * @return string
     */
    abstract protected function getTransferIdentifier(): string;

    /**
     * @return bool
     */
    abstract protected function runTransfer(): bool;

    /**
     * @inheritDoc
     */
    public function setLogger(Logger $logger): void
    {
        $this->logger = $this->transferLoggerFactory->getTransferLoggerByIdentifier($this->getTransferIdentifier(), $logger);
    }

    /**
     * @inheritDoc
     */
    public function start(): void
    {
        $this->startAt = new DateTime();
        $this->sqlLoggerFacade->temporarilyDisableLogging();
    }

    /**
     * @inheritDoc
     */
    public function end(): void
    {
        $this->transferFacade->setAsFinished($this->getTransferIdentifier(), $this->startAt);
        $this->sqlLoggerFacade->reenableLogging();
        $this->em->clear();
    }

    /**
     * @inheritDoc
     */
    public function iterate(): bool
    {
        try {
            $this->transferFacade->setAsInProgress($this->getTransferIdentifier());
            $needNextIteration = $this->runTransfer();

            $this->transferIssueFacade->createMultiple($this->logger->getAllTransferIssuesDataAndCleanQueue());
            $this->em->clear();

            return $needNextIteration;
        } finally {
            $this->transferFacade->setAsFinished($this->getTransferIdentifier(), $this->startAt);
        }
    }

    /**
     * @inheritDoc
     */
    public function isSkipped(): bool
    {
        if ($this->transferFacade->isEnabled($this->getTransferIdentifier()) === false) {
            $this->logger->addInfo(sprintf('Transfer `%s` is skipped, because it is not enabled', $this->getTransferIdentifier()));

            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function wakeUp(): void
    {
        // do nothing
    }

    /**
     * @inheritDoc
     */
    public function sleep(): void
    {
        // do nothing
    }
}
