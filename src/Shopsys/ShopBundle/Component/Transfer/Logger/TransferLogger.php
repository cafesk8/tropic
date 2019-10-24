<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Transfer\Logger;

use Shopsys\ShopBundle\Model\Transfer\Issue\TransferIssueData;
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
     * @var \Shopsys\ShopBundle\Model\Transfer\Issue\TransferIssueData[]
     */
    private $transferIssuesData;

    /**
     * @param string $transferIdentifier
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function __construct(
        string $transferIdentifier,
        Logger $logger
    ) {
        $this->transferIdentifier = $transferIdentifier;
        $this->logger = $logger;
        $this->transferIssuesData = [];
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
        if (!empty($context)) {
            $transferIssueMessage .= sprintf(' (context: %s)', json_encode($context));
        }
        $this->transferIssuesData[] = new TransferIssueData($this->transferIdentifier, $transferIssueMessage);
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Transfer\Issue\TransferIssueData[]
     */
    public function getAllTransferIssuesDataAndCleanQueue(): array
    {
        $transferIssuesData = $this->transferIssuesData;
        $this->transferIssuesData = [];

        return $transferIssuesData;
    }
}
