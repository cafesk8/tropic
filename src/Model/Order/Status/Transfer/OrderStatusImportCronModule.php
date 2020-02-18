<?php

declare(strict_types=1);

namespace App\Model\Order\Status\Transfer;

use App\Component\Domain\DomainHelper;
use App\Component\Rest\Exception\UnexpectedResponseCodeException;
use App\Component\Rest\MultidomainRestClient;
use App\Component\Rest\RestClient;
use App\Component\Transfer\AbstractTransferImportCronModule;
use App\Component\Transfer\Exception\TransferException;
use App\Component\Transfer\Response\TransferResponse;
use App\Component\Transfer\Response\TransferResponseItemDataInterface;
use App\Component\Transfer\TransferCronModuleDependency;
use App\Model\Order\Item\OrderItemFacade;
use App\Model\Order\Order;
use App\Model\Order\OrderDataFactory;
use App\Model\Order\OrderFacade;
use App\Model\Order\Status\OrderStatus;
use App\Model\Order\Status\OrderStatusFacade;
use App\Model\Order\Status\Transfer\Exception\InvalidOrderStatusTransferResponseItemDataException;
use DateTime;

class OrderStatusImportCronModule extends AbstractTransferImportCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_order_statuses';
    private const ORDER_BATCH_SIZE = 50;

    /**
     * @var \App\Component\Rest\MultidomainRestClient
     */
    private $multidomainRestClient;

    /**
     * @var \App\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @var \App\Model\Order\Status\OrderStatusFacade
     */
    private $orderStatusFacade;

    /**
     * @var \App\Model\Order\OrderDataFactory
     */
    private $orderDataFactory;

    /**
     * @var \App\Model\Order\Item\OrderItemFacade
     */
    private $orderItemFacade;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Component\Rest\MultidomainRestClient $multidomainRestClient
     * @param \App\Model\Order\OrderFacade $orderFacade
     * @param \App\Model\Order\Status\OrderStatusFacade $orderStatusFacade
     * @param \App\Model\Order\OrderDataFactory $orderDataFactory
     * @param \App\Model\Order\Item\OrderItemFacade $orderItemFacade
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        MultidomainRestClient $multidomainRestClient,
        OrderFacade $orderFacade,
        OrderStatusFacade $orderStatusFacade,
        OrderDataFactory $orderDataFactory,
        OrderItemFacade $orderItemFacade
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->multidomainRestClient = $multidomainRestClient;
        $this->orderFacade = $orderFacade;
        $this->orderStatusFacade = $orderStatusFacade;
        $this->orderDataFactory = $orderDataFactory;
        $this->orderItemFacade = $orderItemFacade;
    }

    /**
     * @return string
     */
    protected function getTransferIdentifier(): string
    {
        return self::TRANSFER_IDENTIFIER;
    }

    /**
     * @return \App\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponse(): TransferResponse
    {
        $orders = $this->orderFacade->getBatchToCheckOrderStatus(self::ORDER_BATCH_SIZE);
        $orderNumbersByDomain = $this->getOrderNumbersIndexedByDomain($orders);

        $allTransferDataItems = [];
        foreach ($orderNumbersByDomain as $domainId => $orderNumbers) {
            foreach ($orderNumbers as $orderNumber) {
                $this->orderFacade->updateStatusCheckedAtByNumber($orderNumber);
                $source = DomainHelper::DOMAIN_ID_TO_TRANSFER_SOURCE[$domainId];
                $transferDataItems = $this->getTransferItemsFromResponse(
                    $source,
                    $orderNumber,
                    $this->multidomainRestClient->getByDomainId($domainId)
                );

                if (count($transferDataItems) > 0) {
                    $allTransferDataItems = array_merge($allTransferDataItems, $transferDataItems);
                }
            }
        }

        return new TransferResponse(200, $allTransferDataItems);
    }

    /**
     * @param \App\Component\Transfer\Response\TransferResponseItemDataInterface $orderStatusTransferResponseItemData
     */
    protected function processTransferItemData(TransferResponseItemDataInterface $orderStatusTransferResponseItemData): void
    {
        if (!($orderStatusTransferResponseItemData instanceof OrderStatusTransferResponseItemData)) {
            throw new InvalidOrderStatusTransferResponseItemDataException(
                sprintf('Invalid argument passed into method. Instance of `%s` was expected', OrderStatusTransferResponseItemData::class)
            );
        }
        $order = $this->getOrder($orderStatusTransferResponseItemData);

        $orderItemTransferData = $this->getOrderQuantityStatusTransferResponse($order);
        if ($orderItemTransferData === null) {
            $this->logger->addInfo(sprintf(
                'Order status of order with ID `%s`: returned due to transfer data is null',
                $order->getId()
            ));
            return;
        }
        $this->setOrderItemPreparedQuantities($order, $orderItemTransferData->getItems());

        $newOrderStatus = $this->getOrderStatus($orderStatusTransferResponseItemData);
        if ($newOrderStatus->isCanceled()) {
            $this->changeOrderStatus($order, $newOrderStatus);
            return;
        }

        /** @var \App\Model\Order\Status\OrderStatus $orderStatus */
        $orderStatus = $order->getStatus();
        if ($orderStatus->isCheckOrderReadyStatus() === true) {
            $this->processOrderReadyStatusAndOrderItemQuantities($order, $orderItemTransferData);
            return;
        }

        if ($orderStatus === $newOrderStatus) {
            $this->logger->addInfo(sprintf(
                'Order status of order with ID `%s`: returned due to order status is same as new order status',
                $order->getId()
            ));
            return;
        }

        $this->changeOrderStatus($order, $newOrderStatus);
    }

    /**
     * @return bool
     */
    protected function isNextIterationNeeded(): bool
    {
        return true;
    }

    /**
     * @param string $source
     * @param string $orderNumber
     * @param \App\Component\Rest\RestClient $restClient
     * @return array
     */
    private function getTransferItemsFromResponse(string $source, string $orderNumber, RestClient $restClient)
    {
        $apiMethodUrl = sprintf('api/Eshop/GetOrdersStatus?Source=%s&Numbers=%s', $source, $orderNumber);

        $transferDataItems = [];
        try {
            $restResponse = $restClient->get($apiMethodUrl);
        } catch (UnexpectedResponseCodeException $exception) {
            $this->logger->addWarning(
                'Order not found',
                [
                    'number' => $orderNumber,
                ]
            );
            return [];
        }

        $responseData = $restResponse->getData();
        $transferDataItems[] = new OrderStatusTransferResponseItemData($responseData['StatusList'][0]);

        return $transferDataItems;
    }

    /**
     * @param \App\Model\Order\Order[] $orders
     * @return string[][]
     */
    private function getOrderNumbersIndexedByDomain(array $orders): array
    {
        $ordersByDomain = [];
        foreach ($orders as $order) {
            $ordersByDomain[$order->getDomainId()][] = $order->getNumber();
        }

        return $ordersByDomain;
    }

    /**
     * @param \App\Model\Order\Status\Transfer\OrderStatusTransferResponseItemData $orderStatusTransferResponseItemData
     * @return \App\Model\Order\Order
     */
    private function getOrder(OrderStatusTransferResponseItemData $orderStatusTransferResponseItemData): Order
    {
        $orderNumber = $orderStatusTransferResponseItemData->getOrderNumber();
        $order = $this->orderFacade->findByNumber($orderNumber);
        if ($order === null) {
            throw new TransferException(sprintf('Order with number `%s` not found', $orderNumber));
        }

        $this->orderFacade->updateStatusCheckedAtByNumber($orderNumber);

        return $order;
    }

    /**
     * @param \App\Model\Order\Status\Transfer\OrderStatusTransferResponseItemData $orderStatusTransferResponseItemData
     * @return \App\Model\Order\Status\OrderStatus
     */
    private function getOrderStatus(OrderStatusTransferResponseItemData $orderStatusTransferResponseItemData): OrderStatus
    {
        $transferOrderStatus = $orderStatusTransferResponseItemData->getTransferStatus();
        $orderStatus = $this->orderStatusFacade->findByTransferStatus($transferOrderStatus);
        if ($orderStatus === null) {
            throw new TransferException(sprintf('Order status with transfer ID `%s` not found', $transferOrderStatus));
        }

        return $orderStatus;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Model\Order\Status\Transfer\OrderQuantityStatusTransferResponseItemData|null $orderItemTransferData
     */
    private function processOrderReadyStatusAndOrderItemQuantities(Order $order, ?OrderQuantityStatusTransferResponseItemData $orderItemTransferData): void
    {
        if ($orderItemTransferData === null) {
            $this->logger->addInfo(sprintf(
                'Order status of order with ID `%s`: returned due to null transfer data',
                $order->getId()
            ));
            return;
        }

        $orderStatus = $this->orderStatusFacade->getByType(OrderStatus::TYPE_IN_PROGRESS);

        if ($order->getStatus() === $orderStatus) {
            $this->logger->addInfo(sprintf(
                'Order status of order with ID `%s`: returned due to same order status or null order status',
                $order->getId()
            ));
            return;
        }

        $this->changeOrderStatus($order, $orderStatus);
    }

    /**
     * @param \App\Model\Order\Order $order
     * @return \App\Model\Order\Status\Transfer\OrderQuantityStatusTransferResponseItemData|null
     */
    private function getOrderQuantityStatusTransferResponse(Order $order): ?OrderQuantityStatusTransferResponseItemData
    {
        $domainId = $order->getDomainId();
        $source = DomainHelper::DOMAIN_ID_TO_TRANSFER_SOURCE[$domainId];
        $orderNumber = $order->getNumber();
        $apiMethodUrl = sprintf('api/Eshop/GetOrdersQuantityStatus?Source=%s&Numbers=%s', $source, $orderNumber);

        $restClient = $this->multidomainRestClient->getByDomainId($domainId);
        try {
            $restResponse = $restClient->get($apiMethodUrl);
        } catch (UnexpectedResponseCodeException $exception) {
            $this->orderFacade->updateStatusCheckedAtByNumber($orderNumber);
            $this->logger->addWarning(
                'Order not found',
                [
                    'number' => $orderNumber,
                    'error_message' => $exception->getMessage(),
                ]
            );
            return null;
        }

        $responseData = $restResponse->getData();
        return new OrderQuantityStatusTransferResponseItemData($responseData['StatusList'][0]);
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Model\Order\Status\OrderStatus $orderStatus
     */
    private function changeOrderStatus(Order $order, OrderStatus $orderStatus): void
    {
        $orderData = $this->orderDataFactory->createFromOrder($order);
        $oldOrderStatusName = $order->getStatus()->getName('cs');
        $orderData->status = $orderStatus;
        $orderData->statusCheckedAt = new DateTime();

        $locale = DomainHelper::DOMAIN_ID_TO_LOCALE[$order->getDomainId()];
        if ($order->isDeleted()) {
            $this->logger->addInfo(sprintf(
                'Order status of order with ID `%s` has not been changed because is deleted',
                $order->getId()
            ));

            return;
        }
        $order = $this->orderFacade->edit($order->getId(), $orderData, $locale);

        $this->logger->addInfo(sprintf(
            'Order status of order with ID `%s` has been changed from `%s` to `%s`',
            $order->getId(),
            $oldOrderStatusName,
            $order->getStatus()->getName('cs')
        ));
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Model\Order\Status\Transfer\OrderItemQuantityTransferResponseDataItem[] $orderItemQuantityTransferResponseDataItems
     */
    private function setOrderItemPreparedQuantities(Order $order, array $orderItemQuantityTransferResponseDataItems): void
    {
        foreach ($orderItemQuantityTransferResponseDataItems as $item) {
            $this->orderItemFacade->setOrderItemPreparedQuantity($order, $item->getEan(), $item->getPreparedCount());
        }
    }
}
