<?php

declare(strict_types=1);

namespace App\Model\Order\ItemSourceStock;

use App\Model\Store\Store;

class OrderItemSourceStockDataFactory
{
    /**
     * @param \App\Model\Store\Store $stock
     * @param int $quantity
     * @return \App\Model\Order\ItemSourceStock\OrderItemSourceStockData
     */
    public function create(Store $stock, int $quantity): OrderItemSourceStockData
    {
        $orderItemSourceStockData = new OrderItemSourceStockData();
        $orderItemSourceStockData->stock = $stock;
        $orderItemSourceStockData->quantity = $quantity;

        return $orderItemSourceStockData;
    }
}
