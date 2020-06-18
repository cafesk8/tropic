<?php

declare(strict_types=1);

namespace App\Model\Order\ItemSourceStock;

class OrderItemSourceStockData
{
    /**
     * @var \App\Model\Order\Item\OrderItem
     */
    public $orderItem;

    /**
     * @var \App\Model\Store\Store
     */
    public $stock;

    /**
     * @var int
     */
    public $quantity;
}
