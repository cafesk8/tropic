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
     * @var int
     */
    private $stockQuantity;

    /**
     * @var int
     */
    private $variantsCount;

    /**
     * @var \App\Model\Product\View\ListedGroupItem[]
     */
    private $groupItems;

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
     * @param int $stockQuantity
     * @param int $variantsCount
     * @param array[] $groupItems
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
        int $stockQuantity,
        int $variantsCount,
        array $groupItems
    ) {
        parent::__construct($id, $name, $shortDescription, $availability, $sellingPrice, $flagIds, $action, $image);

        $this->stockQuantity = $stockQuantity;
        $this->gifts = $gifts;
        $this->variantsCount = $variantsCount;
        $this->groupItems = array_map(function (array $groupItem) {
            return new ListedGroupItem($groupItem['id'], $groupItem['name'], $groupItem['amount']);
        }, $groupItems);
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
     * @return int
     */
    public function getStockQuantity(): int
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
     * @return \App\Model\Product\View\ListedGroupItem[]
     */
    public function getGroupItems(): array
    {
        return $this->groupItems;
    }
}
