<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Pricing\Transfer;

use ArrayAccess;
use IteratorAggregate;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\ShopBundle\Component\DataObject\ReadObjectAsArrayTrait;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;

class ProductPriceTransferResponseItemData implements TransferResponseItemDataInterface, ArrayAccess, IteratorAggregate
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
     * @var \Shopsys\FrameworkBundle\Component\Money\Money
     */
    private $price;

    /**
     * @var int
     */
    private $domainId;

    /**
     * @param array $restData
     * @param int $domainId
     */
    public function __construct(array $restData, int $domainId)
    {
        $this->number = $restData['Number'];
        $this->barcode = $restData['Barcode'];
        $this->price = Money::createFromFloat($restData['Price'], 6);
        $this->domainId = $domainId;
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
     * @return \Shopsys\FrameworkBundle\Component\Money\Money
     */
    public function getPrice(): Money
    {
        return $this->price;
    }

    /**
     * @return int
     */
    public function getDomainId(): int
    {
        return $this->domainId;
    }
}
