<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Transfer;

use DateTime;
use Shopsys\Plugin\Cron\IteratedCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;

abstract class AbstractTransferCronModule implements IteratedCronModuleInterface, TransferIteratedCronModuleInterface
{
    /**
     * @var \Shopsys\ShopBundle\Component\Transfer\Logger\TransferLogger
     */
    protected $logger;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $em;

    /**
     * @var \Shopsys\ShopBundle\Model\Transfer\TransferFacade
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
     * @var \Shopsys\ShopBundle\Component\Transfer\Logger\TransferLoggerFactory
     */
    private $transferLoggerFactory;

    /**
     * @var \Shopsys\ShopBundle\Component\Transfer\TransferConfig
     */
    private $transferConfig;

    /**
     * @var \DateTime
     */
    private $startAt;

    /**
     * @var \Shopsys\ShopBundle\Model\Transfer\Issue\TransferIssueFacade
     */
    protected $transferIssueFacade;

    /**
     * @param \Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     */
    public function __construct(TransferCronModuleDependency $transferCronModuleDependency)
    {
        $this->em = $transferCronModuleDependency->getEntityManager();
        $this->transferFacade = $transferCronModuleDependency->getTransferFacade();
        $this->sqlLoggerFacade = $transferCronModuleDependency->getSqlLoggerFacade();
        $this->validator = $transferCronModuleDependency->getValidator();
        $this->transferLoggerFactory = $transferCronModuleDependency->getTransferLoggerFactory();
        $this->transferConfig = $transferCronModuleDependency->getTransferConfig();
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
        if ($this->transferConfig->isEnabled() === false) {
            $this->logger->addInfo('All transfers are disabled');

            return true;
        }

        if ($this->transferConfig->areCredentialsFilled() === false) {
            $this->logger->addWarning('Transfer credentials are not filled. All transfers are being skipped');

            return true;
        }

        if ($this->transferFacade->isEnabled($this->getTransferIdentifier()) === false) {
            $this->logger->addInfo('Transfer `%s` is skipped, because it is not enabled');
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
