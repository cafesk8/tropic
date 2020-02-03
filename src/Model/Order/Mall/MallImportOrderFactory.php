<?php

declare(strict_types=1);

namespace App\Model\Order\Mall;

use MPAPI\Entity\Order;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderProductFacade;
use App\Component\Mall\MallImportOrderClient;
use App\Model\Order\OrderFacade;

class MallImportOrderFactory
{
    /**
     * @var \App\Model\Order\Mall\MallImportOrderDataMapper
     */
    private $mallImportOrderDataMapper;

    /**
     * @var \App\Model\Order\Mall\MallImportOrderPreviewFactory
     */
    private $mallImportOrderPreviewFactory;

    /**
     * @var \App\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\Item\OrderProductFacade
     */
    private $orderProductFacade;

    /**
     * @var \App\Component\Mall\MallImportOrderClient
     */
    private $mallImportOrderClient;

    /**
     * @param \App\Model\Order\Mall\MallImportOrderDataMapper $mallImportOrderDataMapper
     * @param \App\Model\Order\Mall\MallImportOrderPreviewFactory $mallImportOrderPreviewFactory
     * @param \App\Model\Order\OrderFacade $orderFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderProductFacade $orderProductFacade
     * @param \App\Component\Mall\MallImportOrderClient $mallImportOrderClient
     */
    public function __construct(
        MallImportOrderDataMapper $mallImportOrderDataMapper,
        MallImportOrderPreviewFactory $mallImportOrderPreviewFactory,
        OrderFacade $orderFacade,
        OrderProductFacade $orderProductFacade,
        MallImportOrderClient $mallImportOrderClient
    ) {
        $this->mallImportOrderDataMapper = $mallImportOrderDataMapper;
        $this->mallImportOrderPreviewFactory = $mallImportOrderPreviewFactory;
        $this->orderFacade = $orderFacade;
        $this->orderProductFacade = $orderProductFacade;
        $this->mallImportOrderClient = $mallImportOrderClient;
    }

    /**
     * @param \MPAPI\Entity\Order $mallOrderDetail
     */
    public function createOrder(Order $mallOrderDetail): void
    {
        $orderData = $this->mallImportOrderDataMapper->createOrderDataFromMallOrderDetail($mallOrderDetail);
        $orderPreview = $this->mallImportOrderPreviewFactory->creteOrderPreview($mallOrderDetail, $orderData);

        $order = $this->orderFacade->createOrder($orderData, $orderPreview, null);
        $this->orderProductFacade->subtractOrderProductsFromStock($order->getProductItems());

        $this->mallImportOrderClient->changeStatus((int)$mallOrderDetail->getOrderId(), Order::STATUS_OPEN, Order::STATUS_OPEN, true);
    }
}
