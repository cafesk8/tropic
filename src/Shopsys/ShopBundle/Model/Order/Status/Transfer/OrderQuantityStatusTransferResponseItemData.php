<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Status\Transfer;

use ArrayAccess;
use IteratorAggregate;
use Shopsys\ShopBundle\Component\DataObject\ReadObjectAsArrayTrait;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;

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
     * @var \Shopsys\ShopBundle\Model\Order\Status\Transfer\OrderItemQuantityTransferResponseDataItem[]
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
     * @return \Shopsys\ShopBundle\Model\Order\Status\Transfer\OrderItemQuantityTransferResponseDataItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
