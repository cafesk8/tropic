<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Status\Transfer;

use ArrayAccess;
use IteratorAggregate;
use Shopsys\ShopBundle\Component\DataObject\ReadObjectAsArrayTrait;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;

class OrderStatusTransferResponseItemData implements TransferResponseItemDataInterface, ArrayAccess, IteratorAggregate
{
    use ReadObjectAsArrayTrait;

    /**
     * @var string
     */
    private $orderNumber;

    /**
     * @var string
     */
    private $transferStatus;

    /**
     * @param array $restDataItem
     */
    public function __construct(array $restDataItem)
    {
        $this->orderNumber = $restDataItem['Number'];
        $this->transferStatus = $restDataItem['Status'];
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
     * @return string
     */
    public function getTransferStatus(): string
    {
        return $this->transferStatus;
    }
}
