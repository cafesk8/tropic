<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Transfer;

use ArrayAccess;
use IteratorAggregate;
use Shopsys\ShopBundle\Component\DataObject\ReadObjectAsArrayTrait;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;

class ProductTransferResponseItemData implements TransferResponseItemDataInterface, ArrayAccess, IteratorAggregate
{
    use ReadObjectAsArrayTrait;

    /**
     * @var int
     */
    private $number;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferResponseItemVariantData[]
     */
    private $variants = [];

    /**
     * @param array $restData
     */
    public function __construct(array $restData)
    {
        $this->number = (int)$restData['Number'];
        $this->name = $restData['Name'];
        $this->description = $restData['Description'];
        foreach ($restData['Barcodes'] as $productVariant) {
            $this->variants[] = new ProductTransferResponseItemVariantData($productVariant);
        }
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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferResponseItemVariantData[]
     */
    public function getVariants(): array
    {
        return $this->variants;
    }
}
