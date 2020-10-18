<?php

declare(strict_types=1);

namespace App\Model\Heureka;

use App\Model\Order\OrderFacade;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;

class HeurekaOrderExportCronModule implements SimpleCronModuleInterface
{
    private OrderFacade $orderFacade;

    private Logger $logger;

    /**
     * @param \App\Model\Order\OrderFacade $orderFacade
     */
    public function __construct(OrderFacade $orderFacade) {
        $this->orderFacade = $orderFacade;
    }

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function run()
    {
        $ordersToExport = $this->orderFacade->getAllForExportToHeureka();
        $exportedOrderIds = [];
        foreach ($ordersToExport as $order) {
            $orderId = $order->getId();
            try {
                $this->orderFacade->sendHeurekaOrderInfo($order, false);
                $exportedOrderIds[] = $orderId;
            } catch (\Exception $exception) {
                $this->logger->addError('Order export to Heureka failed', [
                    'orderId' => $orderId,
                    'exception' => $exception,
                ]);
            }
        }
        if (count($exportedOrderIds) > 0) {
            $this->orderFacade->markOrdersAsExportedToHeureka($exportedOrderIds);
            $this->logger->addInfo('Orders exported to Heureka', [
                'orderIds' => $exportedOrderIds,
            ]);
        } else {
            $this->logger->addInfo('No orders exported to Heureka');
        }
    }
}
