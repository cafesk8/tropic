<?php

declare(strict_types=1);

namespace App\Model\Product\Group;

use App\Model\Product\Product;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="product_groups")
 * @ORM\Entity
 */
class ProductGroup
{
    /**
     * @var \App\Model\Product\Product
     * @ORM\ManyToOne(targetEntity="App\Model\Product\Product", inversedBy="productGroups")
     * @ORM\JoinColumn(nullable=false, name="main_product_id", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\Id
     */
    protected $mainProduct;

    /**
     * @var \App\Model\Product\Product
     * @ORM\ManyToOne(targetEntity="App\Model\Product\Product")
     * @ORM\JoinColumn(nullable=false, name="item_id", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\Id
     */
    protected $item;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $itemCount;

    /**
     * @param \App\Model\Product\Product $mainProduct
     * @param \App\Model\Product\Product $item
     * @param int $itemCount
     */
    public function __construct(
        Product $mainProduct,
        Product $item,
        int $itemCount
    ) {
        $this->mainProduct = $mainProduct;
        $this->item = $item;
        $this->itemCount = $itemCount;
    }

    /**
     * @return \App\Model\Product\Product
     */
    public function getMainProduct(): Product
    {
        return $this->mainProduct;
    }

    /**
     * @return \App\Model\Product\Product
     */
    public function getItem(): Product
    {
        return $this->item;
    }

    /**
     * @return int
     */
    public function getItemCount(): int
    {
        return $this->itemCount;
    }
}
