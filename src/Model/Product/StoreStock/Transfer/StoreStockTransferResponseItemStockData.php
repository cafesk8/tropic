<?php

declare(strict_types=1);

namespace App\Model\Product\StoreStock\Transfer;

use App\Component\DataObject\ReadObjectAsArrayTrait;
use App\Component\Transfer\Response\TransferResponseItemDataInterface;
use ArrayAccess;
use IteratorAggregate;

class StoreStockTransferResponseItemStockData implements TransferResponseItemDataInterface, ArrayAccess, IteratorAggregate
{
    use ReadObjectAsArrayTrait;

    /**
     * @var string
     */
    private $siteNumber;

    /**
     * @var int
     */
    private $quantity;

    /**
     * @var string
     */
    private $parentDataIdentifier;

    /**
     * @param string $parentDataIdentifier
     * @param array $restData
     */
    public function __construct(string $parentDataIdentifier, array $restData)
    {
        $this->siteNumber = trim((string)$restData['SiteNumber']);
        $this->quantity = (int)$restData['Quantity'];
        $this->parentDataIdentifier = $parentDataIdentifier;
    }

    /**
     * @return string
     */
    public function getDataIdentifier(): string
    {
        return $this->parentDataIdentifier . '-' . $this->siteNumber;
    }

    /**
     * @return string
     */
    public function getSiteNumber(): string
    {
        return $this->siteNumber;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }
}
