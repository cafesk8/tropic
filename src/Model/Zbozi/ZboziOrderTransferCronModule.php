<?php

declare(strict_types=1);

namespace App\Model\Zbozi;

use App\Model\Order\OrderFacade;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;

class ZboziOrderTransferCronModule implements SimpleCronModuleInterface
{
    private ZboziFacade $zboziFacade;

    private OrderFacade $orderFacade;

    private Logger $logger;

    /**
     * @param \App\Model\Zbozi\ZboziFacade $zboziFacade
     * @param \App\Model\Order\OrderFacade $orderFacade
     */
    public function __construct(
        ZboziFacade $zboziFacade,
        OrderFacade $orderFacade
    ) {
        $this->zboziFacade = $zboziFacade;
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
        $ordersToExport = $this->orderFacade->getAllForExportToZbozi();
        $exportedOrderIds = [];
        foreach ($ordersToExport as $order) {
            $orderId = $order->getId();
            try {
                $this->zboziFacade->sendOrder($order);
                $exportedOrderIds[] = $orderId;
            } catch (\Exception $exception) {
                $this->logger->addError('Order export to Zbozi failed', [
                    'orderId' => $orderId,
                    'exception' => $exception,
                ]);
            }
        }
        if (count($exportedOrderIds) > 0) {
            $this->orderFacade->markOrdersAsExportedToZbozi($exportedOrderIds);
            $this->logger->addInfo('Orders exported to Zbozi', [
                'orderIds' => $exportedOrderIds,
            ]);
        } else {
            $this->logger->addInfo('No orders exported to Zbozi');
        }
    }
}
