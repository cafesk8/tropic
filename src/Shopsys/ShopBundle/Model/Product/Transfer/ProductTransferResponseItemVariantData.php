<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Transfer;

use ArrayAccess;
use IteratorAggregate;
use Shopsys\ShopBundle\Component\DataObject\ReadObjectAsArrayTrait;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;

class ProductTransferResponseItemVariantData implements TransferResponseItemDataInterface, ArrayAccess, IteratorAggregate
{
    use ReadObjectAsArrayTrait;

    /**
     * @var int
     */
    private $number;

    /**
     * @var int
     */
    private $colorCode;

    /**
     * @var string|null
     */
    private $colorName;

    /**
     * @var int
     */
    private $sizeCode;

    /**
     * @var string|null
     */
    private $sizeName;

    /**
     * @param array $restData
     */
    public function __construct(array $restData)
    {
        $this->number = (int)$restData['Code'];
        $this->colorCode = $restData['ColorCode'];
        $this->colorName = $restData['ColorName'];
        $this->sizeCode = $restData['SizeCode'];
        $this->sizeName = $restData['SizeName'];
    }

    /**
     * @return string
     */
    public function getDataIdentifier(): string
    {
        return (string)$this->number;
    }

    /**
     * @return int
     */
    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * @return int
     */
    public function getColorCode(): int
    {
        return $this->colorCode;
    }

    /**
     * @return string|null
     */
    public function getColorName(): ?string
    {
        return $this->colorName;
    }

    /**
     * @return int
     */
    public function getSizeCode(): int
    {
        return $this->sizeCode;
    }

    /**
     * @return string|null
     */
    public function getSizeName(): ?string
    {
        return $this->sizeName;
    }
}
