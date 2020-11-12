<?php

declare(strict_types=1);

namespace App\Model\Product\View;

use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice;
use Shopsys\ReadModelBundle\Image\ImageView;
use Shopsys\ReadModelBundle\Product\Action\ProductActionView;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductView as BaseListedProductView;

/**
 * @property \App\Model\Product\Pricing\ProductPrice $sellingPrice
 * @property \App\Model\Product\View\ProductActionView $action
 * @method \App\Model\Product\Pricing\ProductPrice getSellingPrice()
 * @method \App\Model\Product\View\ProductActionView getAction()
 */
class ListedProductView extends BaseListedProductView
{
    /**
     * @var string[][]
     */
    private $gifts;

    /**
     * @var int|null
     */
    private $stockQuantity;

    /**
     * @var int
     */
    private $variantsCount;

    /**
     * @var \App\Model\Product\View\ListedSetItem[]
     */
    private $setItems;

    /**
     * @var string|null
     */
    private $deliveryDays;

    /**
     * @var bool
     */
    private $isAvailableInDays;

    /**
     * @var int
     */
    private $realSaleStocksQuantity;

    /**
     * @var string
     */
    private $unitName;

    /**
     * @var \Shopsys\ReadModelBundle\Image\ImageView[]
     */
    private array $stickers;

    private ?int $warranty;

    private bool $recommended;

    private bool $supplierSet;

    private bool $anyVariantInStock;

    /**
     * @param int $id
     * @param string $name
     * @param string|null $shortDescription
     * @param string $availability
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice $sellingPrice
     * @param array $flagIds
     * @param \Shopsys\ReadModelBundle\Product\Action\ProductActionView $action
     * @param \Shopsys\ReadModelBundle\Image\ImageView|null $image
     * @param string[][] $gifts
     * @param int|null $stockQuantity
     * @param int $variantsCount
     * @param \App\Model\Product\View\ListedSetItem[] $setItems
     * @param string|null $deliveryDays
     * @param bool $isAvailableInDays
     * @param int $realSaleStocksQuantity
     * @param string $unitName
     * @param array $stickers
     * @param int|null $warranty
     * @param bool $recommended
     * @param bool $supplierSet
     * @param bool $anyVariantInStock
     */
    public function __construct(
        int $id,
        string $name,
        ?string $shortDescription,
        string $availability,
        ProductPrice $sellingPrice,
        array $flagIds,
        ProductActionView $action,
        ?ImageView $image,
        array $gifts,
        ?int $stockQuantity,
        int $variantsCount,
        array $setItems,
        ?string $deliveryDays,
        bool $isAvailableInDays,
        int $realSaleStocksQuantity,
        string $unitName,
        array $stickers,
        ?int $warranty,
        bool $recommended,
        bool $supplierSet,
        bool $anyVariantInStock
    ) {
        parent::__construct($id, $name, $shortDescription, $availability, $sellingPrice, $flagIds, $action, $image);

        $this->stockQuantity = $stockQuantity ?? 0;
        $this->gifts = $gifts;
        $this->variantsCount = $variantsCount;
        $this->setItems = $setItems;
        $this->deliveryDays = $deliveryDays;
        $this->isAvailableInDays = $isAvailableInDays;
        $this->realSaleStocksQuantity = $realSaleStocksQuantity;
        $this->unitName = $unitName;
        $this->stickers = $stickers;
        $this->warranty = $warranty;
        $this->recommended = $recommended;
        $this->supplierSet = $supplierSet;
        $this->anyVariantInStock = $anyVariantInStock;
    }

    /**
     * @return string[][]
     */
    public function getGifts(): array
    {
        return $this->gifts;
    }

    /**
     * @return string
     */
    public function getFirstGiftLabel(): ?string
    {
        if (empty($this->gifts)) {
            return null;
        }

        return $this->gifts[0]['name'];
    }

    /**
     * @return int
     */
    public function getGiftsCount(): int
    {
        return count($this->gifts);
    }

    /**
     * @return int|null
     */
    public function getStockQuantity(): ?int
    {
        return $this->stockQuantity;
    }

    /**
     * @return int
     */
    public function getVariantsCount(): int
    {
        return $this->variantsCount;
    }

    /**
     * @return \App\Model\Product\View\ListedSetItem[]
     */
    public function getSetItems(): array
    {
        return $this->setItems;
    }

    /**
     * @return string|null
     */
    public function getDeliveryDays(): ?string
    {
        return $this->deliveryDays;
    }

    /**
     * @return bool
     */
    public function isAvailableInDays(): bool
    {
        return $this->isAvailableInDays;
    }

    /**
     * @return int
     */
    public function getRealSaleStocksQuantity(): int
    {
        return $this->realSaleStocksQuantity;
    }

    /**
     * @return string
     */
    public function getUnitName(): string
    {
        return $this->unitName;
    }

    /**
     * @return \Shopsys\ReadModelBundle\Image\ImageView[]
     */
    public function getStickers(): array
    {
        return $this->stickers;
    }

    /**
     * @return int|null
     */
    public function getWarranty(): ?int
    {
        return $this->warranty;
    }

    /**
     * @return bool
     */
    public function isRecommended(): bool
    {
        return $this->recommended;
    }

    /**
     * @return bool
     */
    public function isSupplierSet(): bool
    {
        return $this->supplierSet;
    }

    /**
     * @return bool
     */
    public function isAnyVariantInStock(): bool
    {
        return $this->anyVariantInStock;
    }
}
