<?php

declare(strict_types=1);

namespace App\Model\Order\Status\Transfer;

use App\Component\DataObject\ReadObjectAsArrayTrait;
use App\Component\Transfer\Response\TransferResponseItemDataInterface;
use ArrayAccess;
use IteratorAggregate;

class OrderQuantityStatusTransferResponseItemData implements TransferResponseItemDataInterface, ArrayAccess, IteratorAggregate
{
    use ReadObjectAsArrayTrait;

    /**
     * @var string
     */
    private $orderNumber;

    /**
     * @var bool
     */
    private $orderReady;

    /**
     * @var \App\Model\Order\Status\Transfer\OrderItemQuantityTransferResponseDataItem[]
     */
    private $items;

    /**
     * @param array $restDataItem
     */
    public function __construct(array $restDataItem)
    {
        $this->orderNumber = $restDataItem['Number'];
        $this->orderReady = $restDataItem['OrderReady'];
        $this->items = [];
        if ($restDataItem['Items'] !== null) {
            foreach ($restDataItem['Items'] as $item) {
                $this->items[] = new OrderItemQuantityTransferResponseDataItem($item);
            }
        }
    }

    /**
     * @return string
     */
    public function getDataIdentifier(): string
    {
        return $this->orderNumber;
    }

    /**
     * @return string
     */
    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    /**
     * @return bool
     */
    public function isOrderReady(): bool
    {
        return $this->orderReady;
    }

    /**
     * @return \App\Model\Order\Status\Transfer\OrderItemQuantityTransferResponseDataItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
