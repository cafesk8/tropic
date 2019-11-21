<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Status\Transfer;

use DateTime;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Component\Rest\Exception\UnexpectedResponseCodeException;
use Shopsys\ShopBundle\Component\Rest\MultidomainRestClient;
use Shopsys\ShopBundle\Component\Rest\RestClient;
use Shopsys\ShopBundle\Component\Transfer\AbstractTransferImportCronModule;
use Shopsys\ShopBundle\Component\Transfer\Exception\TransferException;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;
use Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency;
use Shopsys\ShopBundle\Model\Order\Item\OrderItemFacade;
use Shopsys\ShopBundle\Model\Order\Order;
use Shopsys\ShopBundle\Model\Order\OrderDataFactory;
use Shopsys\ShopBundle\Model\Order\OrderFacade;
use Shopsys\ShopBundle\Model\Order\Status\OrderStatus;
use Shopsys\ShopBundle\Model\Order\Status\OrderStatusFacade;
use Shopsys\ShopBundle\Model\Order\Status\Transfer\Exception\InvalidOrderStatusTransferResponseItemDataException;

class OrderStatusImportCronModule extends AbstractTransferImportCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_order_statuses';
    private const ORDER_BATCH_SIZE = 50;

    /**
     * @var \Shopsys\ShopBundle\Component\Rest\MultidomainRestClient
     */
    private $multidomainRestClient;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\Status\OrderStatusFacade
     */
    private $orderStatusFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\OrderDataFactory
     */
    private $orderDataFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\Item\OrderItemFacade
     */
    private $orderItemFacade;

    /**
     * @param \Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \Shopsys\ShopBundle\Component\Rest\MultidomainRestClient $multidomainRestClient
     * @param \Shopsys\ShopBundle\Model\Order\OrderFacade $orderFacade
     * @param \Shopsys\ShopBundle\Model\Order\Status\OrderStatusFacade $orderStatusFacade
     * @param \Shopsys\ShopBundle\Model\Order\OrderDataFactory $orderDataFactory
     * @param \Shopsys\ShopBundle\Model\Order\Item\OrderItemFacade $orderItemFacade
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
     * @return \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse
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
     * @param \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface $orderStatusTransferResponseItemData
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
        $this->setOrderItemPreparedQuantities($order, $orderItemTransferData->getItems());

        /** @var \Shopsys\ShopBundle\Model\Order\Status\OrderStatus $orderStatus */
        $orderStatus = $order->getStatus();
        if ($orderStatus->isCheckOrderReadyStatus() === true) {
            $this->processOrderReadyStatusAndOrderItemQuantities($order, $orderItemTransferData);
            return;
        }

        $newOrderStatus = $this->getOrderStatus($orderStatusTransferResponseItemData);

        if ($orderStatus === $newOrderStatus) {
            return;
        }

        if ($orderStatus->isOrderStatusReady() && $newOrderStatus->isCheckOrderReadyStatus() === true) {
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
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $restClient
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
     * @param \Shopsys\ShopBundle\Model\Order\Order[] $orders
     * @return \Shopsys\ShopBundle\Model\Order\Order[]
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
     * @param \Shopsys\ShopBundle\Model\Order\Status\Transfer\OrderStatusTransferResponseItemData $orderStatusTransferResponseItemData
     * @return \Shopsys\ShopBundle\Model\Order\Order
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
     * @param \Shopsys\ShopBundle\Model\Order\Status\Transfer\OrderStatusTransferResponseItemData $orderStatusTransferResponseItemData
     * @return \Shopsys\ShopBundle\Model\Order\Status\OrderStatus
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
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param \Shopsys\ShopBundle\Model\Order\Status\Transfer\OrderQuantityStatusTransferResponseItemData|null $orderItemTransferData
     */
    private function processOrderReadyStatusAndOrderItemQuantities(Order $order, ?OrderQuantityStatusTransferResponseItemData $orderItemTransferData): void
    {
        if ($orderItemTransferData === null) {
            return;
        }

        $orderStatus = null;
        $isOrderSendToStore = $order->getStoreExternalNumber() !== null;
        if ($orderItemTransferData->isOrderReady() === true) {
            $orderStatusType = $isOrderSendToStore === true ? OrderStatus::TYPE_READY_STORE : OrderStatus::TYPE_READY;
            $orderStatus = $this->orderStatusFacade->getByType($orderStatusType);
        } else {
            $isAlmostReady = $this->checkAlmostReadyByItems($orderItemTransferData->getItems());
            if ($isAlmostReady === true) {
                $orderStatusType = $isOrderSendToStore === true ? OrderStatus::TYPE_ALMOST_READY_STORE : OrderStatus::TYPE_ALMOST_READY;
                $orderStatus = $this->orderStatusFacade->getByType($orderStatusType);
            }
        }

        if ($order->getStatus() === $orderStatus || $orderStatus === null) {
            return;
        }

        $this->changeOrderStatus($order, $orderStatus);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return \Shopsys\ShopBundle\Model\Order\Status\Transfer\OrderQuantityStatusTransferResponseItemData|null
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
                ]
            );
            return null;
        }

        $responseData = $restResponse->getData();
        return new OrderQuantityStatusTransferResponseItemData($responseData['StatusList'][0]);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param \Shopsys\ShopBundle\Model\Order\Status\OrderStatus $orderStatus
     */
    private function changeOrderStatus(Order $order, OrderStatus $orderStatus): void
    {
        $orderData = $this->orderDataFactory->createFromOrder($order);
        $oldOrderStatusName = $order->getStatus()->getName('cs');
        $orderData->status = $orderStatus;
        $orderData->statusCheckedAt = new DateTime();

        $locale = DomainHelper::DOMAIN_ID_TO_LOCALE[$order->getDomainId()];
        $order = $this->orderFacade->edit($order->getId(), $orderData, $locale);

        $this->logger->addInfo(sprintf(
            'Order status of order with ID `%s` has been changed from `%s` to `%s`',
            $order->getId(),
            $oldOrderStatusName,
            $order->getStatus()->getName('cs')
        ));
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Status\Transfer\OrderItemQuantityTransferResponseDataItem[] $items
     * @return bool
     */
    private function checkAlmostReadyByItems(array $items): bool
    {
        $preparedItemCount = 0;
        foreach ($items as $item) {
            $preparedItemCount += $item->getPreparedCount();
        }

        return $preparedItemCount > 0;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param \Shopsys\ShopBundle\Model\Order\Status\Transfer\OrderItemQuantityTransferResponseDataItem[] $orderItemQuantityTransferResponseDataItems
     */
    private function setOrderItemPreparedQuantities(Order $order, array $orderItemQuantityTransferResponseDataItems): void
    {
        foreach ($orderItemQuantityTransferResponseDataItems as $item) {
            $this->orderItemFacade->setOrderItemPreparedQuantity($order, $item->getEan(), $item->getPreparedCount());
        }
    }
}
