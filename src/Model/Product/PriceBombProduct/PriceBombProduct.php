<?php

declare(strict_types=1);

namespace App\Model\Product\PriceBombProduct;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Product\Product;

/**
 * @ORM\Table(name="products_price_bomb")
 * @ORM\Entity
 */
class PriceBombProduct
{
    /**
     * @var \App\Model\Product\Product
     *
     * @ORM\ManyToOne(targetEntity="Shopsys\FrameworkBundle\Model\Product\Product")
     * @ORM\JoinColumn(nullable=false, name="product_id", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\Id
     */
    protected $product;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     */
    protected $domainId;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    protected $position;

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @param int $position
     */
    public function __construct(Product $product, int $domainId, int $position)
    {
        $this->product = $product;
        $this->domainId = $domainId;
        $this->position = $position;
    }

    /**
     * @return \App\Model\Product\Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }
}
