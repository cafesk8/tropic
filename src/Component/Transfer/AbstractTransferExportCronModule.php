<?php

declare(strict_types=1);

namespace App\Component\Transfer;

use Exception;
use App\Component\Rest\Exception\RestException;
use App\Component\Transfer\Response\TransferResponse;

abstract class AbstractTransferExportCronModule extends AbstractTransferCronModule
{
    /**
     * @return array
     */
    abstract protected function getDataForExport(): array;

    /**
     * @param array $item
     * @return \App\Component\Transfer\Response\TransferResponse
     */
    abstract protected function getTransferResponse(array $item): TransferResponse;

    /**
     * @param int|string $itemIdentifier
     * @param \App\Component\Transfer\Response\TransferResponse $transferResponse
     */
    abstract protected function processExportResponse($itemIdentifier, TransferResponse $transferResponse): void;

    /**
     * @return bool
     */
    protected function runTransfer(): bool
    {
        $dataForExport = $this->getDataForExport();

        if (count($dataForExport) === 0) {
            return false;
        }

        foreach ($dataForExport as $itemIdentifier => $item) {
            $this->em->beginTransaction();
            try {
                $transferResponse = $this->getTransferResponse($item);
                $this->processExportResponse($itemIdentifier, $transferResponse);

                $this->em->commit();
            } catch (RestException $exception) {
                $this->logger->addError(
                    sprintf(
                        'Transfer of item with ID `%s` was aborted because of transfer connection: %s',
                        $itemIdentifier,
                        $exception->getMessage()
                    )
                );
                if ($this->em->isOpen()) {
                    $this->em->rollback();
                    $this->em->clear();
                }
            } catch (Exception $exception) {
                $this->logger->addError(
                    sprintf(
                        'Transfer of item with ID `%s` was aborted because of unexpected exception: %s',
                        $itemIdentifier,
                        $exception->getMessage()
                    )
                );
                if ($this->em->isOpen()) {
                    $this->em->rollback();
                    $this->em->clear();
                }

                throw $exception;
            }
        }

        $this->em->clear();

        return false;
    }
}
