<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Status\Transfer;

use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Component\Rest\Exception\UnexpectedResponseCodeException;
use Shopsys\ShopBundle\Component\Rest\MultidomainRestClient;
use Shopsys\ShopBundle\Component\Rest\RestClient;
use Shopsys\ShopBundle\Component\Transfer\AbstractTransferImportCronModule;
use Shopsys\ShopBundle\Component\Transfer\Exception\TransferException;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;
use Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency;
use Shopsys\ShopBundle\Model\Order\Order;
use Shopsys\ShopBundle\Model\Order\OrderDataFactory;
use Shopsys\ShopBundle\Model\Order\OrderFacade;
use Shopsys\ShopBundle\Model\Order\Status\OrderStatus;
use Shopsys\ShopBundle\Model\Order\Status\OrderStatusFacade;
use Shopsys\ShopBundle\Model\Order\Status\Transfer\Exception\InvalidOrderStatusTransferResponseItemDataException;

class OrderStatusImportCronModule extends AbstractTransferImportCronModule
{
    private const TRANSFER_IDENTIFIER = 'import_order_statuses';
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
     * @param \Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \Shopsys\ShopBundle\Component\Rest\MultidomainRestClient $multidomainRestClient
     * @param \Shopsys\ShopBundle\Model\Order\OrderFacade $orderFacade
     * @param \Shopsys\ShopBundle\Model\Order\Status\OrderStatusFacade $orderStatusFacade
     * @param \Shopsys\ShopBundle\Model\Order\OrderDataFactory $orderDataFactory
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        MultidomainRestClient $multidomainRestClient,
        OrderFacade $orderFacade,
        OrderStatusFacade $orderStatusFacade,
        OrderDataFactory $orderDataFactory
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->multidomainRestClient = $multidomainRestClient;
        $this->orderFacade = $orderFacade;
        $this->orderStatusFacade = $orderStatusFacade;
        $this->orderDataFactory = $orderDataFactory;
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
        $orderStatus = $this->getOrderStatus($orderStatusTransferResponseItemData);
        $orderData = $this->orderDataFactory->createFromOrder($order);

        if ($order->getStatus() === $orderStatus) {
            return;
        }

        $orderData->status = $orderStatus;
        $orderData->statusCheckedAt = new \DateTime();
        $this->orderFacade->edit($order->getId(), $orderData);

        $this->logger->addInfo(sprintf('Order status of order with ID `%s` has been changed', $order->getId()));
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
            $this->orderFacade->updateStatusCheckedAtByNumber($orderNumber);
            $this->logger->addWarning(sprintf('Order with number `%s` not found', $orderNumber));
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
}
