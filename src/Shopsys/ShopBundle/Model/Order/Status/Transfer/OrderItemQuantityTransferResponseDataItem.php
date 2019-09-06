<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Status\Transfer;

use ArrayAccess;
use IteratorAggregate;
use Shopsys\ShopBundle\Component\DataObject\ReadObjectAsArrayTrait;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;

class OrderItemQuantityTransferResponseDataItem implements TransferResponseItemDataInterface, ArrayAccess, IteratorAggregate
{
    use ReadObjectAsArrayTrait;

    /**
     * @var string
     */
    private $ean;

    /**
     * @var int|null;
     */
    private $orderedCount;

    /**
     * @var int|null;
     */
    private $preparedCount;

    /**
     * @var int|null;
     */
    private $soldCount;

    /**
     * @param array $restData
     */
    public function __construct(array $restData)
    {
        $this->ean = $restData['Barcode'];
        $this->orderedCount = (int)$restData['Ordered'];
        $this->preparedCount = (int)$restData['Prepared'];
        $this->soldCount = (int)$restData['Sold'];
    }

    /**
     * @return string
     */
    public function getDataIdentifier(): string
    {
        return $this->ean;
    }

    /**
     * @return string
     */
    public function getEan(): string
    {
        return $this->ean;
    }

    /**
     * @return int|null
     */
    public function getOrderedCount(): ?int
    {
        return $this->orderedCount;
    }

    /**
     * @return int|null
     */
    public function getPreparedCount(): ?int
    {
        return $this->preparedCount;
    }

    /**
     * @return int|null
     */
    public function getSoldCount(): ?int
    {
        return $this->soldCount;
    }
}
