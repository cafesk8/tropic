<?php

declare(strict_types=1);

namespace App\Model\Order\Transfer;

use App\Component\Rest\RestClient;
use App\Component\String\StringHelper;
use App\Component\Transfer\AbstractTransferExportCronModule;
use App\Component\Transfer\Response\TransferResponse;
use App\Component\Transfer\TransferCronModuleDependency;
use App\Model\Order\OrderFacade;

class OrderExportCronModule extends AbstractTransferExportCronModule
{
    private const TRANSFER_IDENTIFIER = 'export_orders';
    private const ORDER_EXPORT_BATCH_SIZE = 100;

    /**
     * @var \App\Component\Rest\RestClient
     */
    private $restClient;

    /**
     * @var \App\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @var \App\Model\Order\Transfer\OrderExportMapper
     */
    private $orderExportMapper;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Component\Rest\RestClient $restClient
     * @param \App\Model\Order\OrderFacade $orderFacade
     * @param \App\Model\Order\Transfer\OrderExportMapper $orderExportMapper
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        RestClient $restClient,
        OrderFacade $orderFacade,
        OrderExportMapper $orderExportMapper
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->restClient = $restClient;
        $this->orderFacade = $orderFacade;
        $this->orderExportMapper = $orderExportMapper;
    }

    /**
     * @return string
     */
    protected function getTransferIdentifier(): string
    {
        return self::TRANSFER_IDENTIFIER;
    }

    /**
     * @return array
     */
    protected function getDataForExport(): array
    {
        $ordersReadyForExport = $this->orderFacade->getReadyOrdersForExportBatch(self::ORDER_EXPORT_BATCH_SIZE);
        $ordersForExport = [];

        foreach ($ordersReadyForExport as $orderReadyForExport) {
            $ordersForExport[$orderReadyForExport->getId()] = $this->orderExportMapper->mapToArray($orderReadyForExport);
        }

        return $ordersForExport;
    }

    /**
     * @param array $orderData
     * @return \App\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponse(array $orderData): TransferResponse
    {
        $restResponse = $this->restClient->post('api/Eshop/NewOrder', $orderData);

        return new TransferResponse($restResponse->getCode(), $restResponse->getData());
    }

    /**
     * @inheritDoc
     */
    protected function processExportResponse($itemIdentifier, TransferResponse $transferResponse): void
    {
        if ($transferResponse->getStatusCode() !== 200) {
            $this->orderFacade->markOrderAsFailedExported($itemIdentifier);
            $this->logger->addError(sprintf(
                'Order with id `%s` was not exported, because of bad response code `%s`',
                $itemIdentifier,
                $transferResponse->getStatusCode()
            ));
        }

        $responseData = $transferResponse->getResponseData();
        if (array_key_exists('Error', $responseData) && $responseData['Error'] === true) {
            $this->orderFacade->markOrderAsFailedExported($itemIdentifier);
            $this->logger->addWarning(sprintf(
                'Order with id `%s` was not exported, because of error `%s`',
                $itemIdentifier,
                StringHelper::removeNewline((string)$responseData['ErrorMessage'])
            ));
        } else {
            $this->orderFacade->markOrderAsExported($itemIdentifier);
            $this->logger->addInfo(sprintf('Order with id `%s` was exported successfully', $itemIdentifier));
        }
    }
}
