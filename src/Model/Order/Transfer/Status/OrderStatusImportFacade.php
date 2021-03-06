<?php

declare(strict_types=1);

namespace App\Model\Order\Transfer\Status;

use App\Component\Transfer\Logger\TransferLogger;
use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Component\Transfer\Pohoda\Order\Status\PohodaOrderStatus;
use App\Component\Transfer\Pohoda\Order\Status\PohodaOrderStatusExportFacade;
use App\Model\Order\Order;
use App\Model\Order\OrderDataFactory;
use App\Model\Order\OrderFacade;
use App\Model\Order\Status\OrderStatusDataFactory;
use App\Model\Order\Status\OrderStatusFacade;
use App\Model\Product\Transfer\ProductInfoQueueImportFacade;
use DateTime;
use Shopsys\FrameworkBundle\Component\Domain\Domain;

class OrderStatusImportFacade
{
    private const ORDER_STATUSES_IMPORT_MAX_BATCH_LIMIT = 1000;

    private OrderStatusQueueImportFacade $orderStatusQueueImportFacade;

    private PohodaOrderStatusExportFacade $pohodaOrderStatusExportFacade;

    private TransferLogger $logger;

    private OrderFacade $orderFacade;

    private OrderStatusFacade $orderStatusFacade;

    private OrderStatusDataFactory $orderStatusDataFactory;

    private Domain $domain;

    private OrderDataFactory $orderDataFactory;

    private ProductInfoQueueImportFacade $productInfoQueueImportFacade;

    /**
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Model\Order\Transfer\Status\OrderStatusQueueImportFacade $orderStatusQueueImportFacade
     * @param \App\Component\Transfer\Pohoda\Order\Status\PohodaOrderStatusExportFacade $pohodaOrderStatusExportFacade
     * @param \App\Model\Order\OrderFacade $orderFacade
     * @param \App\Model\Order\Status\OrderStatusFacade $orderStatusFacade
     * @param \App\Model\Order\Status\OrderStatusDataFactory $orderStatusDataFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Order\OrderDataFactory $orderDataFactory
     * @param \App\Model\Product\Transfer\ProductInfoQueueImportFacade $productInfoQueueImportFacade
     */
    public function __construct(
        TransferLoggerFactory $transferLoggerFactory,
        OrderStatusQueueImportFacade $orderStatusQueueImportFacade,
        PohodaOrderStatusExportFacade $pohodaOrderStatusExportFacade,
        OrderFacade $orderFacade,
        OrderStatusFacade $orderStatusFacade,
        OrderStatusDataFactory $orderStatusDataFactory,
        Domain $domain,
        OrderDataFactory $orderDataFactory,
        ProductInfoQueueImportFacade $productInfoQueueImportFacade
    ) {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(OrderStatusImportCronModule::TRANSFER_IDENTIFIER);
        $this->orderStatusQueueImportFacade = $orderStatusQueueImportFacade;
        $this->pohodaOrderStatusExportFacade = $pohodaOrderStatusExportFacade;
        $this->orderFacade = $orderFacade;
        $this->orderStatusFacade = $orderStatusFacade;
        $this->orderStatusDataFactory = $orderStatusDataFactory;
        $this->domain = $domain;
        $this->orderDataFactory = $orderDataFactory;
        $this->productInfoQueueImportFacade = $productInfoQueueImportFacade;
    }

    /**
     * @return bool
     */
    public function processImport(): bool
    {
        $changedPohodaOrderIds = $this->orderStatusQueueImportFacade->getChangedPohodaOrderIds(self::ORDER_STATUSES_IMPORT_MAX_BATCH_LIMIT);
        $pohodaOrderStatuses = $this->pohodaOrderStatusExportFacade->getPohodaOrderStatusesByPohodaIds(
            $changedPohodaOrderIds
        );

        $pohodaOrderIdsToRemoveFromQueue = [];
        if (count($pohodaOrderStatuses) === 0) {
            $this->logger->addInfo('Nejsou ????dn?? data ve front?? ke zpracov??n??');
        } else {
            $this->logger->addInfo('Prob??hne ulo??en?? stav?? objedn??vek z fronty', ['pohodaOrderStatusesCount' => count($pohodaOrderStatuses)]);
            $pohodaOrderIdsToRemoveFromQueue = $this->updateOrdersByPohodaOrders($pohodaOrderStatuses);
        }

        if (count($pohodaOrderIdsToRemoveFromQueue) > 0) {
            $this->logger->addInfo('Prob??hne odstran??n?? objedn??vek z fronty', ['pohodaOrderIdsToRemoveFromQueue' => count($pohodaOrderIdsToRemoveFromQueue)]);
            $this->orderStatusQueueImportFacade->removeOrdersFromQueue($pohodaOrderIdsToRemoveFromQueue);
        }

        $this->logger->persistTransferIssues();

        return  !$this->orderStatusQueueImportFacade->isQueueEmpty() && count($changedPohodaOrderIds) === self::ORDER_STATUSES_IMPORT_MAX_BATCH_LIMIT;
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Order\Status\PohodaOrderStatus[] $pohodaOrderStatuses
     * @return array
     */
    private function updateOrdersByPohodaOrders(array $pohodaOrderStatuses): array
    {
        $pohodaOrderIdsToRemoveFromQueue = [];
        foreach ($pohodaOrderStatuses as $pohodaOrderStatus) {
            $order = $this->orderFacade->findByPohodaId($pohodaOrderStatus->pohodaOrderId);

            if ($order !== null) {
                $this->editOrderStatus($order, $pohodaOrderStatus);
            } else {
                $this->logger->addError('Objedn??vka v e-shopu neexistuje', [
                    'pohodaOrderId' => $pohodaOrderStatus->pohodaOrderId,
                ]);
            }

            $pohodaOrderIdsToRemoveFromQueue[] = $pohodaOrderStatus->pohodaOrderId;
        }

        return array_filter($pohodaOrderIdsToRemoveFromQueue);
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param \App\Component\Transfer\Pohoda\Order\Status\PohodaOrderStatus $pohodaOrderStatus
     */
    private function editOrderStatus(Order $order, PohodaOrderStatus $pohodaOrderStatus): void
    {
        $orderStatus = $this->orderStatusFacade->findByTransferStatus($pohodaOrderStatus->statusName);
        if ($orderStatus === null) {
            $this->logger->addInfo('Stav neexistuje a bude vytvo??en', [
                'statusId' => $pohodaOrderStatus->pohodaStatusId,
                'name' => $pohodaOrderStatus->statusName,
            ]);

            $orderStatusData = $this->orderStatusDataFactory->create();
            $orderStatusData->transferStatus = $pohodaOrderStatus->statusName;
            foreach ($this->domain->getAllLocales() as $locale) {
                $orderStatusData->name[$locale] = $pohodaOrderStatus->statusName;
            }
            $orderStatus = $this->orderStatusFacade->create($orderStatusData);
        }
        $oldOrderStatus = $order->getStatus();

        $orderData = $this->orderDataFactory->createFromOrder($order);
        $orderData->status = $orderStatus;
        $orderDomain = $this->domain->getDomainConfigById($order->getDomainId());
        $order = $this->orderFacade->edit($order->getId(), $orderData, $orderDomain->getLocale());
        $orderProductPohodaIds = [];

        foreach ($order->getProductItems() as $productItem) {
            $orderProductPohodaIds[] = $productItem->getProduct()->getPohodaId();
        }

        $this->productInfoQueueImportFacade->insertChangedPohodaProductIds($orderProductPohodaIds, new DateTime());

        $this->logger->addInfo('Stav objedn??vky aktualizov??n', [
            'orderId' => $order->getId(),
            'orderPohodaId' => $pohodaOrderStatus->pohodaOrderId,
            'oldStatus' => $oldOrderStatus->getName($orderDomain->getLocale()),
            'newStatus' => $pohodaOrderStatus->statusName,
        ]);
    }
}
