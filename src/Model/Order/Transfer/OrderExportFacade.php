<?php

declare(strict_types=1);

namespace App\Model\Order\Transfer;

use App\Component\Transfer\Logger\TransferLogger;
use App\Model\Customer\Transfer\CustomerExportFacade;
use App\Model\Order\OrderFacade;

class OrderExportFacade
{
    /**
     * @var \App\Model\Customer\Transfer\CustomerExportFacade
     */
    private $customerExportFacade;

    /**
     * @var \App\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @param \App\Model\Customer\Transfer\CustomerExportFacade $customerExportFacade
     * @param \App\Model\Order\OrderFacade $orderFacade
     */
    public function __construct(
        CustomerExportFacade $customerExportFacade,
        OrderFacade $orderFacade
    ) {
        $this->customerExportFacade = $customerExportFacade;
        $this->orderFacade = $orderFacade;
    }

    public function processExport(): void
    {
        $orders = $this->orderFacade->findAll();

        $customersUsers = [];
        foreach ($orders as $order) {
            if ($order->getCustomerUser() !== null) {
                $customersUsers[$order->getCustomerUser()->getId()] = $order->getCustomerUser();
            }
        }

        $this->customerExportFacade->processExportCustomersUsers($customersUsers);
    }
}
