<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Transfer;

use Exception;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;

abstract class AbstractTransferExportCronModule extends AbstractTransferCronModule
{
    /**
     * @return array
     */
    abstract protected function getDataForExport(): array;

    /**
     * @param array $item
     * @return \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse
     */
    abstract protected function getTransferResponse(array $item): TransferResponse;

    /**
     * @param int|string $itemIdentifier
     * @param \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse $transferResponse
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
            } catch (Exception $exception) {
                $this->logger->addError(
                    sprintf(
                        'Transfer of item with ID `%s` was aborted because of unexpected exception: %s',
                        $itemIdentifier,
                        $exception->getMessage()
                    )
                );
                $this->em->rollback();
                $this->em->clear();
            }
        }

        $this->em->clear();

        return false;
    }
}
