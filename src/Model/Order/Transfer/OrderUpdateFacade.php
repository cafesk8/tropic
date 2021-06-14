<?php

declare(strict_types=1);

namespace App\Model\Order\Transfer;

use App\Component\Domain\DomainHelper;
use App\Component\Transfer\Logger\TransferLogger;
use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Model\Order\OrderFacade;
use App\Model\Payment\Payment;
use App\Model\Payment\PaymentFacade;
use Exception;

class OrderUpdateFacade
{
    private OrderFacade $orderFacade;

    private OrderUpdateRepository $orderUpdateRepository;

    private PaymentFacade $paymentFacade;

    private TransferLogger $logger;

    /**
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Model\Order\OrderFacade $orderFacade
     * @param \App\Model\Order\Transfer\OrderUpdateRepository $orderUpdateRepository
     * @param \App\Model\Payment\PaymentFacade $paymentFacade
     */
    public function __construct(
        TransferLoggerFactory $transferLoggerFactory,
        OrderFacade $orderFacade,
        OrderUpdateRepository $orderUpdateRepository,
        PaymentFacade $paymentFacade
    ) {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(OrderExportCronModule::TRANSFER_IDENTIFIER);
        $this->orderFacade = $orderFacade;
        $this->orderUpdateRepository = $orderUpdateRepository;
        $this->paymentFacade = $paymentFacade;
    }

    public function processUpdate(): void
    {
        $this->logger->addInfo('Začátek aktualizace objednávek v Pohodě');

        $payments = $this->paymentFacade->getAll();
        $paymentNames = [];
        $orders = $this->orderFacade->getAllForUpdate(OrderExportFacade::ORDERS_EXPORT_MAX_BATCH_LIMIT);

        foreach (DomainHelper::LOCALES as $locale) {
            $paymentNames[$locale] = array_map(fn (Payment $payment) => $payment->getName($locale), $payments);
        }

        foreach ($orders as $order) {
            try {
                $rowsAffected = $this->orderUpdateRepository->updatePaymentMethod($order, $paymentNames);
                $this->orderFacade->markOrderAsExported($order->getId(), $order->getPohodaId());
                $this->logger->addInfo('Objednávka v Pohodě byla aktualizována', [
                    'orderNumber' => $order->getNumber(),
                    'orderId' => $order->getId(),
                    'rowsAffected' => $rowsAffected,
                ]);
            } catch (Exception $exception) {
                $this->logger->addError('Aktualizace objednávky v Pohodě selhala', [
                    'orderNumber' => $order->getNumber(),
                    'orderId' => $order->getId(),
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $this->logger->addInfo('Konec aktualizace objednávek v Pohodě');
        $this->logger->persistTransferIssues();
    }
}
