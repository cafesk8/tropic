<?php

declare(strict_types=1);

namespace App\Model\Order\ItemSourceStock;

use App\Model\Order\Item\OrderItem;
use App\Model\Store\Store;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="order_item_source_stocks")
 * @ORM\Entity
 */
class OrderItemSourceStock
{
    /**
     * @var \App\Model\Order\Item\OrderItem
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\Model\Order\Item\OrderItem")
     * @ORM\JoinColumn(name="order_item_id", nullable=false, referencedColumnName="id")
     */
    private $orderItem;

    /**
     * @var \App\Model\Store\Store
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\Model\Store\Store")
     * @ORM\JoinColumn(name="stock_id", nullable=false, referencedColumnName="id")
     */
    private $stock;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $quantity;

    /**
     * @param \App\Model\Order\ItemSourceStock\OrderItemSourceStockData $orderItemSourceStockData
     */
    public function __construct(OrderItemSourceStockData $orderItemSourceStockData)
    {
        $this->orderItem = $orderItemSourceStockData->orderItem;
        $this->stock = $orderItemSourceStockData->stock;
        $this->quantity = $orderItemSourceStockData->quantity;
    }

    /**
     * @return \App\Model\Order\Item\OrderItem
     */
    public function getOrderItem(): OrderItem
    {
        return $this->orderItem;
    }

    /**
     * @return \App\Model\Store\Store
     */
    public function getStock(): Store
    {
        return $this->stock;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }
}
