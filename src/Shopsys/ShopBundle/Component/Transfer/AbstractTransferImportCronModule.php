<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Transfer;

use Exception;
use Shopsys\ShopBundle\Component\Transfer\Exception\TransferException;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;
use Symfony\Component\Validator\Validator\TraceableValidator;

abstract class AbstractTransferImportCronModule extends AbstractTransferCronModule
{
    /**
     * @return \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse
     */
    abstract protected function getTransferResponse(): TransferResponse;

    /**
     * @param \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface $itemData
     */
    abstract protected function processTransferItemData(TransferResponseItemDataInterface $itemData): void;

    /**
     * @return bool
     */
    abstract protected function isNextIterationNeeded(): bool;

    /**
     * @inheritDoc
     */
    protected function runTransfer(): bool
    {
        $transferResponse = $this->getTransferResponse();

        if ($transferResponse->isEmpty()) {
            return false;
        }

        $this->processItems($transferResponse);

        // Big non-incremental transfers (eg. products categories, products domains visibilities) have huge response
        unset($transferResponse);

        $this->em->clear();

        return $this->isNextIterationNeeded();
    }

    /**
     * @param \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse $transferResponse
     */
    private function processItems(TransferResponse $transferResponse): void
    {
        foreach ($transferResponse->getResponseData() as $responseItemData) {
            $this->em->beginTransaction();
            try {
                $this->processTransferItemData($responseItemData);
                $this->em->commit();
            } catch (TransferException $transferException) {
                $this->logger->addWarning(
                    sprintf(
                        'Transfer of item with identifier `%s` was aborted because: %s',
                        $responseItemData->getDataIdentifier(),
                        $transferException->getMessage()
                    )
                );
                $this->em->rollback();
            } catch (Exception $exception) {
                $this->logger->addError(
                    sprintf(
                        'Transfer of item with identifier `%s` was aborted. '
                        . 'This error will be reported to Shopsys. Reason of this error: %s',
                        $responseItemData->getDataIdentifier(),
                        $exception->getMessage()
                    )
                );
                $this->em->clear();

                if ($this->em->isOpen()) {
                    $this->em->rollback();
                }

                throw $exception;
            } finally {
                // Application in DEV mode uses TraceableValidator for validation. TraceableValidator saves data from
                // validation in memory, so it can consume quite a lot of memory, which leads to transfer crash
                if ($this->validator instanceof TraceableValidator) {
                    $this->validator->reset();
                }
            }
        }
    }
}
