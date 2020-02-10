<?php

declare(strict_types=1);

namespace App\Model\Product\Pricing\Transfer;

use App\Component\DataObject\ReadObjectAsArrayTrait;
use App\Component\Transfer\Response\TransferResponseItemDataInterface;
use ArrayAccess;
use IteratorAggregate;
use Shopsys\FrameworkBundle\Component\Money\Money;

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
     * @var \Shopsys\FrameworkBundle\Component\Money\Money
     */
    private $actionPrice;

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
        $this->price = Money::createFromFloat($restData['FullPrice'], 6);
        $this->actionPrice = Money::createFromFloat($restData['Price'], 6);
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
     * @return \Shopsys\FrameworkBundle\Component\Money\Money
     */
    public function getActionPrice(): Money
    {
        return $this->actionPrice;
    }

    /**
     * @return bool
     */
    public function isActionPrice(): bool
    {
        return $this->price->equals($this->actionPrice) === false;
    }

    /**
     * @return int
     */
    public function getDomainId(): int
    {
        return $this->domainId;
    }
}
