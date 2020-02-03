<?php

declare(strict_types=1);

namespace App\Model\Product\StoreStock\Transfer;

use ArrayAccess;
use IteratorAggregate;
use App\Component\DataObject\ReadObjectAsArrayTrait;
use App\Component\Transfer\Response\TransferResponseItemDataInterface;

class StoreStockTransferResponseItemData implements TransferResponseItemDataInterface, ArrayAccess, IteratorAggregate
{
    use ReadObjectAsArrayTrait;

    /**
     * @var string
     */
    private $number;

    /**
     * @var string
     */
    private $barcode;

    /**
     * @var \App\Model\Product\StoreStock\Transfer\StoreStockTransferResponseItemStockData[]
     */
    private $sitesQuantity = [];

    /**
     * @param array $restData
     */
    public function __construct(array $restData)
    {
        $this->number = (string)$restData['Number'];
        $this->barcode = $restData['Barcode'];
        if (isset($restData['SitesQuantity'])) {
            foreach ($restData['SitesQuantity'] as $stockQuantity) {
                $this->sitesQuantity[] = new StoreStockTransferResponseItemStockData($this->getDataIdentifier(), $stockQuantity);
            }
        }
    }

    /**
     * @return string
     */
    public function getDataIdentifier(): string
    {
        return $this->barcode;
    }

    /**
     * @return string
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * @return string
     */
    public function getBarcode(): string
    {
        return $this->barcode;
    }

    /**
     * @return \App\Model\Product\StoreStock\Transfer\StoreStockTransferResponseItemStockData[]
     */
    public function getSitesQuantity(): array
    {
        return $this->sitesQuantity;
    }
}
