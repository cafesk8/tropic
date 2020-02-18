<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\DataObject\ReadObjectAsArrayTrait;
use App\Component\Transfer\Response\TransferResponseItemDataInterface;
use ArrayAccess;
use IteratorAggregate;

class ProductTransferResponseItemData implements TransferResponseItemDataInterface, ArrayAccess, IteratorAggregate
{
    use ReadObjectAsArrayTrait;

    /**
     * @var string
     */
    private $transferNumber;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var \App\Model\Product\Transfer\ProductTransferResponseItemVariantData[]
     */
    private $variants = [];

    /**
     * @param array $restData
     */
    public function __construct(array $restData)
    {
        $this->transferNumber = (string)$restData['Number'];
        $this->name = $restData['Name'];
        $this->description = $restData['Description'];
        if (isset($restData['Barcodes'])) {
            foreach ($restData['Barcodes'] as $productVariant) {
                $this->variants[] = new ProductTransferResponseItemVariantData($productVariant);
            }
        }
    }

    /**
     * @return string
     */
    public function getDataIdentifier(): string
    {
        return (string)$this->transferNumber;
    }

    /**
     * @return string
     */
    public function getTransferNumber(): string
    {
        return $this->transferNumber;
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
     * @return \App\Model\Product\Transfer\ProductTransferResponseItemVariantData[]
     */
    public function getVariants(): array
    {
        return $this->variants;
    }
}
