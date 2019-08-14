<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Mall;

use MPAPI\Entity\Order;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderProductFacade;
use Shopsys\ShopBundle\Component\Mall\MallImportOrderClient;
use Shopsys\ShopBundle\Model\Order\OrderFacade;

class MallImportOrderFactory
{
    /**
     * @var \Shopsys\ShopBundle\Model\Order\Mall\MallImportOrderDataMapper
     */
    private $mallImportOrderDataMapper;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\Mall\MallImportOrderPreviewFactory
     */
    private $mallImportOrderPreviewFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\Item\OrderProductFacade
     */
    private $orderProductFacade;

    /**
     * @var \Shopsys\ShopBundle\Component\Mall\MallImportOrderClient
     */
    private $mallImportOrderClient;

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Mall\MallImportOrderDataMapper $mallImportOrderDataMapper
     * @param \Shopsys\ShopBundle\Model\Order\Mall\MallImportOrderPreviewFactory $mallImportOrderPreviewFactory
     * @param \Shopsys\ShopBundle\Model\Order\OrderFacade $orderFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\Item\OrderProductFacade $orderProductFacade
     * @param \Shopsys\ShopBundle\Component\Mall\MallImportOrderClient $mallImportOrderClient
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

        $order = $this->orderFacade->createOrder($orderData, $orderPreview);
        $this->orderProductFacade->subtractOrderProductsFromStock($order->getProductItems());

        $this->mallImportOrderClient->changeStatus($mallOrderDetail->getOrderId(), Order::STATUS_OPEN, true);
    }
}
