<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Transfer;

use Exception;
use Shopsys\ShopBundle\Component\Rest\RestClient;
use Shopsys\ShopBundle\Component\Transfer\AbstractTransferExportCronModule;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;
use Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency;
use Shopsys\ShopBundle\Model\Order\OrderFacade;

class OrderExportCronModule extends AbstractTransferExportCronModule
{
    private const TRANSFER_IDENTIFIER = 'export_orders';

    /**
     * @var \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    private $restClient;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\Transfer\OrderExportMapper
     */
    private $orderExportMapper;

    /**
     * @param \Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $restClient
     * @param \Shopsys\ShopBundle\Model\Order\OrderFacade $orderFacade
     * @param \Shopsys\ShopBundle\Model\Order\Transfer\OrderExportMapper $orderExportMapper
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
        $notExportedOrders = $this->orderFacade->getNotExportedOrders();
        $ordersToExport = [];

        foreach ($notExportedOrders as $notExportedOrder) {
            $ordersToExport[$notExportedOrder->getId()] = $this->orderExportMapper->mapToArray($notExportedOrder);
        }

        return $ordersToExport;
    }

    /**
     * @param array $orderData
     * @return \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponse(array $orderData): TransferResponse
    {
        $restResponse = $this->restClient->post('api/Eshop/NewOrder', $orderData);

        return new TransferResponse($restResponse->getCode());
    }

    /**
     * @param int|string $orderIdentifier
     * @param \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse $transferResponse
     */
    protected function markItemAsExported($orderIdentifier, TransferResponse $transferResponse): void
    {
        $this->orderFacade->markOrderAsExported($orderIdentifier);
        $this->logger->addInfo(sprintf('Order with id %s was successfully exported', $orderIdentifier));
    }

    /**
     * @param int|string $orderIdentifier
     * @param \Exception $exception
     */
    protected function markItemAsFailedExported($orderIdentifier, Exception $exception): void
    {
        $this->orderFacade->markOrderAsFailedExported($orderIdentifier);
    }
}
