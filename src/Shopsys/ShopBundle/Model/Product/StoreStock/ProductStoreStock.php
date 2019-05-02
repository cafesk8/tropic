<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\StoreStock;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Store\Store;

/**
 * @ORM\Entity
 * @ORM\Table(name="product_store_stocks")
 */
class ProductStoreStock
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\Product
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Shopsys\FrameworkBundle\Model\Product\Product", inversedBy="storeStocks")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $product;

    /**
     * @var \Shopsys\ShopBundle\Model\Store\Store
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Shopsys\ShopBundle\Model\Store\Store")
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
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Shopsys\ShopBundle\Model\Store\Store $store
     * @param int|null $stockQuantity
     */
    public function __construct(Product $product, Store $store, ?int $stockQuantity)
    {
        $this->product = $product;
        $this->store = $store;
        $this->stockQuantity = $stockQuantity;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Shopsys\ShopBundle\Model\Store\Store $store
     * @param int|null $stockQuantity
     * @return \Shopsys\ShopBundle\Model\Product\StoreStock\ProductStoreStock
     */
    public static function create(Product $product, Store $store, ?int $stockQuantity): self
    {
        return new static($product, $store, $stockQuantity);
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Product\Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Store\Store
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
}
