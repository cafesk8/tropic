<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Backup;

use League\Flysystem\FilesystemInterface;

class PohodaTransferBackup
{
    private string $transferXmlBackupPath;

    private FilesystemInterface $filesystem;

    /**
     * @param string $transferXmlBackupPath
     * @param \League\Flysystem\FilesystemInterface $filesystem
     */
    public function __construct(string $transferXmlBackupPath, FilesystemInterface $filesystem)
    {
        $this->transferXmlBackupPath = $transferXmlBackupPath;
        $this->filesystem = $filesystem;
    }

    /**
     * @param string|null $xmlBackupIdentifier
     * @param string|null $xmlData
     * @param int $timestamp
     * @param string $type
     */
    public function backupXml(?string $xmlBackupIdentifier, ?string $xmlData, int $timestamp, string $type): void
    {
        if ($xmlBackupIdentifier !== null && $xmlData !== null) {
            $backupXmlFileName = $xmlBackupIdentifier . '_' . $timestamp . '.xml';
            $backupXmlFilePath = $this->transferXmlBackupPath . '/' . $type . '/' . $xmlBackupIdentifier . '/' . $backupXmlFileName;
            $this->filesystem->put($backupXmlFilePath, $xmlData);
        }
    }

    /**
     * @param int $deleteOldBackupFilesSeconds
     * @return int
     */
    public function deleteOldBackupFiles(int $deleteOldBackupFilesSeconds): int
    {
        $deletedCounter = 0;
        $currentTimestamp = time();
        $backupFiles = $this->filesystem->listContents($this->transferXmlBackupPath, true);
        foreach ($backupFiles as $backupFile) {
            if ($backupFile['type'] === 'file' && $currentTimestamp - $backupFile['timestamp'] >= $deleteOldBackupFilesSeconds) {
                $this->filesystem->delete($backupFile['path']);
                $deletedCounter++;
            }
        }

        return $deletedCounter;
    }
}
