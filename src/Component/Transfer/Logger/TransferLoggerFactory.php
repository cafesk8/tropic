<?php

declare(strict_types=1);

namespace App\Component\Transfer\Logger;

use App\Model\Transfer\Issue\TransferIssueFacade;
use Symfony\Bridge\Monolog\Logger;

class TransferLoggerFactory
{
    /**
     * @var \App\Component\Transfer\Logger\TransferLogger[]
     */
    private $transferLoggers;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $defaultLogger;

    /**
     * @var \App\Model\Transfer\Issue\TransferIssueFacade
     */
    private $transferIssueFacade;

    /**
     * @param \Symfony\Bridge\Monolog\Logger $defaultLogger
     * @param \App\Model\Transfer\Issue\TransferIssueFacade $transferIssueFacade
     */
    public function __construct(Logger $defaultLogger, TransferIssueFacade $transferIssueFacade)
    {
        $this->defaultLogger = $defaultLogger;
        $this->transferLoggers = [];
        $this->transferIssueFacade = $transferIssueFacade;
    }

    /**
     * @param string $transferIdentifier
     * @param \Symfony\Bridge\Monolog\Logger $logger
     * @return \App\Component\Transfer\Logger\TransferLogger
     */
    private function create($transferIdentifier, Logger $logger): TransferLogger
    {
        return new TransferLogger(
            $transferIdentifier,
            $logger,
            $this->transferIssueFacade
        );
    }

    /**
     * @param string $transferIdentifier
     * @param \Symfony\Bridge\Monolog\Logger $logger
     * @return \App\Component\Transfer\Logger\TransferLogger
     */
    public function getTransferLoggerByIdentifier($transferIdentifier, ?Logger $logger = null): TransferLogger
    {
        if ($logger === null) {
            $logger = $this->defaultLogger;
        }
        $transferLoggerKey = $transferIdentifier . '_' . spl_object_hash($logger);

        if (!array_key_exists($transferIdentifier, $this->transferLoggers)) {
            $this->transferLoggers[$transferLoggerKey] = $this->create($transferIdentifier, $logger);
        }

        return $this->transferLoggers[$transferLoggerKey];
    }
}
