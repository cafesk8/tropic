<?php

declare(strict_types=1);

namespace App\Model\Order\Status\Transfer;

use App\Component\DataObject\ReadObjectAsArrayTrait;
use App\Component\Transfer\Response\TransferResponseItemDataInterface;
use ArrayAccess;
use IteratorAggregate;

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
        $this->orderNumber = (string)$restDataItem['Number'];
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
