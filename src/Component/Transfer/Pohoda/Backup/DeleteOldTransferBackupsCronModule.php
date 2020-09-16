<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Backup;

use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;

class DeleteOldTransferBackupsCronModule implements SimpleCronModuleInterface
{
    private const DELETE_OLD_BACKUPS_SECONDS = 259200;

    protected Logger $logger;

    private PohodaTransferBackup $pohodaTransferBackup;

    /**
     * @param \App\Component\Transfer\Pohoda\Backup\PohodaTransferBackup $pohodaTransferBackup
     */
    public function __construct(PohodaTransferBackup $pohodaTransferBackup)
    {
        $this->pohodaTransferBackup = $pohodaTransferBackup;
    }

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    public function run(): void
    {
        $count = $this->pohodaTransferBackup->deleteOldBackupFiles(self::DELETE_OLD_BACKUPS_SECONDS);
        $this->logger->info(sprintf('%d transfer backup files were deleted from.', $count));
    }
}
