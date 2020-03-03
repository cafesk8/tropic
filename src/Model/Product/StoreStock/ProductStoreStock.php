<?php

declare(strict_types=1);

namespace App\Model\Product\StoreStock;

use App\Model\Product\Product;
use App\Model\Store\Store;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="product_store_stocks")
 */
class ProductStoreStock
{
    /**
     * @var \App\Model\Product\Product
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\Model\Product\Product", inversedBy="storeStocks")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $product;

    /**
     * @var \App\Model\Store\Store
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\Model\Store\Store")
     * @ORM\JoinColumn(name="store_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $store;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $stockQuantity;

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Store\Store $store
     * @param int|null $stockQuantity
     */
    public function __construct(Product $product, Store $store, ?int $stockQuantity)
    {
        $this->product = $product;
        $this->store = $store;
        $this->stockQuantity = $stockQuantity;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Store\Store $store
     * @param int|null $stockQuantity
     * @return \App\Model\Product\StoreStock\ProductStoreStock
     */
    public static function create(Product $product, Store $store, ?int $stockQuantity): self
    {
        return new self($product, $store, $stockQuantity);
    }

    /**
     * @return \App\Model\Product\Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @return \App\Model\Store\Store
     */
    public function getStore(): Store
    {
        return $this->store;
    }

    /**
     * @return int|null
     */
    public function getStockQuantity(): ?int
    {
        return $this->stockQuantity;
    }

    /**
     * @param int $quantity
     */
    public function subtractStockQuantity(int $quantity)
    {
        $this->stockQuantity -= $quantity;
    }
}
