<?php

declare(strict_types=1);

namespace App\Component\Transfer\Logger;

use App\Model\Transfer\Issue\TransferIssueData;
use App\Model\Transfer\Issue\TransferIssueFacade;
use Symfony\Bridge\Monolog\Logger;

class TransferLogger
{
    /**
     * @var string
     */
    private $transferIdentifier;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @var \App\Model\Transfer\Issue\TransferIssueData[]
     */
    private $transferIssuesData;

    /**
     * @var string
     */
    private $transferIssuesGroupId;

    /**
     * @var \App\Model\Transfer\Issue\TransferIssueFacade
     */
    private $transferIssueFacade;

    /**
     * @param string $transferIdentifier
     * @param \Symfony\Bridge\Monolog\Logger $logger
     * @param \App\Model\Transfer\Issue\TransferIssueFacade $transferIssueFacade
     */
    public function __construct(
        string $transferIdentifier,
        Logger $logger,
        TransferIssueFacade $transferIssueFacade
    ) {
        $this->transferIdentifier = $transferIdentifier;
        $this->logger = $logger;
        $this->transferIssuesData = [];
        $this->transferIssuesGroupId = uniqid($transferIdentifier . '_');
        $this->transferIssueFacade = $transferIssueFacade;
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function addDebug($message, array $context = []): void
    {
        $this->logger->addDebug($this->getLoggerMessage($message), $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function addInfo($message, array $context = []): void
    {
        $this->logger->addInfo($this->getLoggerMessage($message), $context);
        $this->addTransferIssueDataToQueue($message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function addWarning($message, array $context = []): void
    {
        $this->logger->addDebug($this->getLoggerMessage($message), $context);
        $this->addTransferIssueDataToQueue($message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function addError($message, array $context = []): void
    {
        $this->logger->addError($this->getLoggerMessage($message), $context);
        $this->addTransferIssueDataToQueue($message, $context);
    }

    /**
     * @param string $message
     * @return string
     */
    private function getLoggerMessage($message): string
    {
        return 'Transfer "' . $this->transferIdentifier . '": ' . $message;
    }

    /**
     * @param string $message
     * @param array $context
     */
    private function addTransferIssueDataToQueue(string $message, array $context): void
    {
        $transferIssueMessage = $message;
        $contextString = null;
        if (!empty($context)) {
            $contextString = json_encode($context);
        }
        $this->transferIssuesData[] = new TransferIssueData($this->transferIdentifier, $transferIssueMessage, $this->transferIssuesGroupId, $contextString);
    }

    /**
     * @return \App\Model\Transfer\Issue\TransferIssueData[]
     */
    public function getAllTransferIssuesDataAndCleanQueue(): array
    {
        $transferIssuesData = $this->transferIssuesData;
        $this->transferIssuesData = [];

        return $transferIssuesData;
    }

    /**
     * @return int
     */
    public function getAllTransferIssuesDataCount(): int
    {
        return count($this->transferIssuesData);
    }

    public function persistTransferIssues(): void
    {
        $transferIssuesData = $this->getAllTransferIssuesDataAndCleanQueue();

        if (count($transferIssuesData) > 0) {
            $this->transferIssueFacade->createMultiple($transferIssuesData);
        }
    }
}
