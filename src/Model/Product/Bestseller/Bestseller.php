<?php

declare(strict_types=1);

namespace App\Model\Product\Bestseller;

use App\Model\Product\Product;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="products_bestseller")
 * @ORM\Entity
 */
class Bestseller
{
    /**
     * @var \App\Model\Product\Product
     *
     * @ORM\ManyToOne(targetEntity="App\Model\Product\Product")
     * @ORM\JoinColumn(nullable=false, name="product_id", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\Id
     */
    private Product $product;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     */
    private int $domainId;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private int $position;

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

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @return int
     */
    public function getDomainId(): int
    {
        return $this->domainId;
    }
}