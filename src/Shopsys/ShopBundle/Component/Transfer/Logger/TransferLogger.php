<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Transfer\Logger;

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
     * @param string $transferIdentifier
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function __construct(
        $transferIdentifier,
        Logger $logger
    ) {
        $this->transferIdentifier = $transferIdentifier;
        $this->logger = $logger;
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
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function addError($message, array $context = []): void
    {
        $this->logger->addError($this->getLoggerMessage($message), $context);
    }

    /**
     * @param string $message
     * @return string
     */
    private function getLoggerMessage($message): string
    {
        return 'Transfer "' . $this->transferIdentifier . '": ' . $message;
    }
}
