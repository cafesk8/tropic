<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Transfer\Logger;

use Symfony\Bridge\Monolog\Logger;

class TransferLoggerFactory
{
    /**
     * @var \Shopsys\ShopBundle\Component\Transfer\Logger\TransferLogger[]
     */
    private $transferLoggers;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $defaultLogger;

    /**
     * @param \Symfony\Bridge\Monolog\Logger $defaultLogger
     */
    public function __construct(
        Logger $defaultLogger
    ) {
        $this->defaultLogger = $defaultLogger;
        $this->transferLoggers = [];
    }

    /**
     * @param string $transferIdentifier
     * @param \Symfony\Bridge\Monolog\Logger $logger
     * @return \Shopsys\ShopBundle\Component\Transfer\Logger\TransferLogger
     */
    private function create($transferIdentifier, Logger $logger): TransferLogger
    {
        return new TransferLogger(
            $transferIdentifier,
            $logger
        );
    }

    /**
     * @param string $transferIdentifier
     * @param \Symfony\Bridge\Monolog\Logger $logger
     * @return \Shopsys\ShopBundle\Component\Transfer\Logger\TransferLogger
     */
    public function getTransferLoggerByIdentifier($transferIdentifier, Logger $logger = null): TransferLogger
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
