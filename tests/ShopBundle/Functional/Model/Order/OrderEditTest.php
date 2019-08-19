<?php

declare(strict_types=1);

namespace Tests\ShopBundle\Functional\Model\Order;

use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItem;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation;
use Shopsys\FrameworkBundle\Model\Order\Order;
use Shopsys\FrameworkBundle\Model\Order\OrderData;
use Shopsys\FrameworkBundle\Model\Order\OrderDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation;
use Shopsys\ShopBundle\DataFixtures\Demo\OrderDataFixture;
use Tests\FrameworkBundle\Test\IsMoneyEqual;
use Tests\ShopBundle\Test\TransactionFunctionalTestCase;

final class OrderEditTest extends TransactionFunctionalTestCase
{
    private const ORDER_ID = 10;
    private const PRODUCT_ITEM_ID = 45;
    private const PAYMENT_ITEM_ID = 46;
    private const TRANSPORT_ITEM_ID = 47;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\Order
     */
    private $order;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\OrderDataFactoryInterface
     */
    private $orderDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemPriceCalculation
     */
    private $orderItemPriceCalculation;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemFactoryInterface
     */
    private $orderItemFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\Item\OrderItemDataFactoryInterface
     */
    private $orderItemDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\OrderPriceCalculation
     */
    private $orderPriceCalculation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->order = $this->getReference(OrderDataFixture::ORDER_PREFIX . self::ORDER_ID);

        $this->orderDataFactory = $this->getContainer()->get(OrderDataFactoryInterface::class);
        $this->orderItemPriceCalculation = $this->getContainer()->get(OrderItemPriceCalculation::class);
        $this->orderItemFactory = $this->getContainer()->get(OrderItemFactoryInterface::class);
        $this->orderItemDataFactory = $this->getContainer()->get(OrderItemDataFactoryInterface::class);
        $this->orderPriceCalculation = $this->getContainer()->get(OrderPriceCalculation::class);
    }

    public function testEditProductItem(): void
    {
        $orderData = $this->orderDataFactory->createFromOrder($this->order);

        $orderItemData = $orderData->itemsWithoutTransportAndPayment[self::PRODUCT_ITEM_ID];
        $orderItemData->quantity = 10;
        $orderItemData->vatPercent = '50.00';
        $orderItemData->priceWithVat = Money::create(100);

        $this->order->edit($orderData, $this->orderItemPriceCalculation, $this->orderItemFactory, $this->orderPriceCalculation);

        $orderItem = $this->order->getItemById(self::PRODUCT_ITEM_ID);
        $this->assertThat($orderItem->getPriceWithVat(), new IsMoneyEqual(Money::create(100)));
        $this->assertThat($orderItem->getPriceWithoutVat(), new IsMoneyEqual(Money::create('66.67')));
        $this->assertThat($orderItem->getTotalPriceWithVat(), new IsMoneyEqual(Money::create(1000)));
        $this->assertNull($orderItem->getTotalPriceWithoutVat());

        $this->assertThat($this->order->getTotalPriceWithVat(), new IsMoneyEqual(Money::create(1300)));
        $this->assertThat($this->order->getTotalPriceWithoutVat(), new IsMoneyEqual(Money::create('931.960000')));
    }

    public function testEditProductItemWithoutUsingPriceCalculation(): void
    {
        $orderData = $this->orderDataFactory->createFromOrder($this->order);

        $orderItemData = $orderData->itemsWithoutTransportAndPayment[self::PRODUCT_ITEM_ID];
        $orderItemData->quantity = 10;
        $orderItemData->usePriceCalculation = false;
        $orderItemData->vatPercent = '50.00';
        $orderItemData->priceWithVat = Money::create(100);
        $orderItemData->priceWithoutVat = Money::create(50);
        $orderItemData->totalPriceWithVat = Money::create(950);
        $orderItemData->totalPriceWithoutVat = Money::create(400);

        $this->order->edit($orderData, $this->orderItemPriceCalculation, $this->orderItemFactory, $this->orderPriceCalculation);

        $orderItem = $this->order->getItemById(self::PRODUCT_ITEM_ID);
        $this->assertThat($orderItem->getPriceWithVat(), new IsMoneyEqual(Money::create(100)));
        $this->assertThat($orderItem->getPriceWithoutVat(), new IsMoneyEqual(Money::create(50)));
        $this->assertThat($orderItem->getTotalPriceWithVat(), new IsMoneyEqual(Money::create(950)));
        $this->assertThat($orderItem->getTotalPriceWithoutVat(), new IsMoneyEqual(Money::create(400)));

        $this->assertThat($this->order->getTotalPriceWithVat(), new IsMoneyEqual(Money::create('1250.000000')));
        $this->assertThat($this->order->getTotalPriceWithoutVat(), new IsMoneyEqual(Money::create('665.290000')));
    }

    public function testAddProductItem(): void
    {
        $orderData = $this->orderDataFactory->createFromOrder($this->order);

        $orderItemData = $this->orderItemDataFactory->create();
        $orderItemData->name = 'new item';
        $orderItemData->quantity = 10;
        $orderItemData->vatPercent = '50.00';
        $orderItemData->priceWithVat = Money::create(100);
        $orderData->itemsWithoutTransportAndPayment[OrderData::NEW_ITEM_PREFIX . '1'] = $orderItemData;

        $this->order->edit($orderData, $this->orderItemPriceCalculation, $this->orderItemFactory, $this->orderPriceCalculation);

        $orderItem = $this->getOrderItemByName($this->order, 'new item');
        $this->assertThat($orderItem->getPriceWithVat(), new IsMoneyEqual(Money::create(100)));
        $this->assertThat($orderItem->getPriceWithoutVat(), new IsMoneyEqual(Money::create('66.67')));
        $this->assertThat($orderItem->getTotalPriceWithVat(), new IsMoneyEqual(Money::create(1000)));
        $this->assertNull($orderItem->getTotalPriceWithoutVat());

        $this->assertThat($this->order->getTotalPriceWithVat(), new IsMoneyEqual(Money::create(19143)));
        $this->assertThat($this->order->getTotalPriceWithoutVat(), new IsMoneyEqual(Money::create('15678.240000')));
    }

    public function testAddProductItemWithoutUsingPriceCalculation(): void
    {
        $orderData = $this->orderDataFactory->createFromOrder($this->order);

        $orderItemData = $this->orderItemDataFactory->create();
        $orderItemData->name = 'new item';
        $orderItemData->quantity = 10;
        $orderItemData->usePriceCalculation = false;
        $orderItemData->vatPercent = '50.00';
        $orderItemData->priceWithVat = Money::create(100);
        $orderItemData->priceWithoutVat = Money::create(50);
        $orderItemData->totalPriceWithVat = Money::create(950);
        $orderItemData->totalPriceWithoutVat = Money::create(400);
        $orderData->itemsWithoutTransportAndPayment[OrderData::NEW_ITEM_PREFIX . '1'] = $orderItemData;

        $this->order->edit($orderData, $this->orderItemPriceCalculation, $this->orderItemFactory, $this->orderPriceCalculation);

        $orderItem = $this->getOrderItemByName($this->order, 'new item');
        $this->assertThat($orderItem->getPriceWithVat(), new IsMoneyEqual(Money::create(100)));
        $this->assertThat($orderItem->getPriceWithoutVat(), new IsMoneyEqual(Money::create(50)));
        $this->assertThat($orderItem->getTotalPriceWithVat(), new IsMoneyEqual(Money::create(950)));
        $this->assertThat($orderItem->getTotalPriceWithoutVat(), new IsMoneyEqual(Money::create(400)));

        $this->assertThat($this->order->getTotalPriceWithVat(), new IsMoneyEqual(Money::create(19093)));
        $this->assertThat($this->order->getTotalPriceWithoutVat(), new IsMoneyEqual(Money::create('15411.570000')));
    }

    public function testEditTransportItem(): void
    {
        $orderData = $this->orderDataFactory->createFromOrder($this->order);

        $orderItemData = $orderData->orderTransport;
        $orderItemData->vatPercent = '50.00';
        $orderItemData->priceWithVat = Money::create(100);

        $this->order->edit($orderData, $this->orderItemPriceCalculation, $this->orderItemFactory, $this->orderPriceCalculation);

        $orderItem = $this->order->getItemById(self::TRANSPORT_ITEM_ID);
        $this->assertThat($orderItem->getPriceWithVat(), new IsMoneyEqual(Money::create(100)));
        $this->assertThat($orderItem->getPriceWithoutVat(), new IsMoneyEqual(Money::create('66.67')));
        $this->assertThat($orderItem->getTotalPriceWithVat(), new IsMoneyEqual(Money::create(100)));
        $this->assertNull($orderItem->getTotalPriceWithoutVat());

        $this->assertThat($this->order->getTotalPriceWithVat(), new IsMoneyEqual(Money::create(18043)));
        $this->assertThat($this->order->getTotalPriceWithoutVat(), new IsMoneyEqual(Money::create('14912.950000')));
    }

    public function testEditTransportItemWithoutUsingPriceCalculation(): void
    {
        $orderData = $this->orderDataFactory->createFromOrder($this->order);

        $orderItemData = $orderData->orderTransport;
        $orderItemData->usePriceCalculation = false;
        $orderItemData->vatPercent = '50.00';
        $orderItemData->priceWithVat = Money::create(100);
        $orderItemData->priceWithoutVat = Money::create(50);
        $orderItemData->totalPriceWithVat = Money::create(100);
        $orderItemData->totalPriceWithoutVat = Money::create(50);

        $this->order->edit($orderData, $this->orderItemPriceCalculation, $this->orderItemFactory, $this->orderPriceCalculation);

        $orderItem = $this->order->getItemById(self::TRANSPORT_ITEM_ID);
        $this->assertThat($orderItem->getPriceWithVat(), new IsMoneyEqual(Money::create(100)));
        $this->assertThat($orderItem->getPriceWithoutVat(), new IsMoneyEqual(Money::create(50)));
        $this->assertThat($orderItem->getTotalPriceWithVat(), new IsMoneyEqual(Money::create(100)));
        $this->assertThat($orderItem->getTotalPriceWithoutVat(), new IsMoneyEqual(Money::create(50)));

        $this->assertThat($this->order->getTotalPriceWithVat(), new IsMoneyEqual(Money::create(18043)));
        $this->assertThat($this->order->getTotalPriceWithoutVat(), new IsMoneyEqual(Money::create('14896.280000')));
    }

    public function testEditPaymentItem(): void
    {
        $orderData = $this->orderDataFactory->createFromOrder($this->order);

        $orderItemData = $orderData->orderPayment;
        $orderItemData->vatPercent = '50.00';
        $orderItemData->priceWithVat = Money::create(100);

        $this->order->edit($orderData, $this->orderItemPriceCalculation, $this->orderItemFactory, $this->orderPriceCalculation);

        $orderItem = $this->order->getItemById(self::PAYMENT_ITEM_ID);
        $this->assertThat($orderItem->getPriceWithVat(), new IsMoneyEqual(Money::create(100)));
        $this->assertThat($orderItem->getPriceWithoutVat(), new IsMoneyEqual(Money::create('66.67')));
        $this->assertThat($orderItem->getTotalPriceWithVat(), new IsMoneyEqual(Money::create(100)));
        $this->assertNull($orderItem->getTotalPriceWithoutVat());

        $this->assertThat($this->order->getTotalPriceWithVat(), new IsMoneyEqual(Money::create(18143)));
        $this->assertThat($this->order->getTotalPriceWithoutVat(), new IsMoneyEqual(Money::create('14978.240000')));
    }

    public function testEditPaymentItemWithoutUsingPriceCalculation(): void
    {
        $orderData = $this->orderDataFactory->createFromOrder($this->order);

        $orderItemData = $orderData->orderPayment;
        $orderItemData->usePriceCalculation = false;
        $orderItemData->vatPercent = '50.00';
        $orderItemData->priceWithVat = Money::create(100);
        $orderItemData->priceWithoutVat = Money::create(50);
        $orderItemData->totalPriceWithVat = Money::create(100);
        $orderItemData->totalPriceWithoutVat = Money::create(50);

        $this->order->edit($orderData, $this->orderItemPriceCalculation, $this->orderItemFactory, $this->orderPriceCalculation);

        $orderItem = $this->order->getItemById(self::PAYMENT_ITEM_ID);
        $this->assertThat($orderItem->getPriceWithVat(), new IsMoneyEqual(Money::create(100)));
        $this->assertThat($orderItem->getPriceWithoutVat(), new IsMoneyEqual(Money::create(50)));
        $this->assertThat($orderItem->getTotalPriceWithVat(), new IsMoneyEqual(Money::create(100)));
        $this->assertThat($orderItem->getTotalPriceWithoutVat(), new IsMoneyEqual(Money::create(50)));

        $this->assertThat($this->order->getTotalPriceWithVat(), new IsMoneyEqual(Money::create(18143)));
        $this->assertThat($this->order->getTotalPriceWithoutVat(), new IsMoneyEqual(Money::create('14961.570000')));
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Order $order
     * @param string $name
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\OrderItem
     */
    private function getOrderItemByName(Order $order, string $name): OrderItem
    {
        foreach ($order->getItems() as $orderItem) {
            if ($orderItem->getName() === $name) {
                return $orderItem;
            }
        }

        throw new \RuntimeException(sprintf('Order item with the name "%s" was not found in the order.', $name));
    }
}
