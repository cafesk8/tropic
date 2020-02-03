<?php

declare(strict_types=1);

namespace App\Model\Product\Gift;

use Shopsys\FrameworkBundle\Component\Money\Money;
use App\Model\Product\Product;

class ProductGiftInCart
{
    /**
     * @var \App\Model\Product\Product
     */
    private $product;

    /**
     * @var \App\Model\Product\Product
     */
    private $gift;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money
     */
    private $price;

    /**
     * @var int
     */
    private $quantity;

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Product\Product $gift
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $price
     * @param int $quantity
     */
    public function __construct(Product $product, Product $gift, Money $price, int $quantity)
    {
        $this->product = $product;
        $this->gift = $gift;
        $this->price = $price;
        $this->quantity = $quantity;
    }

    /**
     * @return \App\Model\Product\Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @return \App\Model\Product\Product
     */
    public function getGift(): Product
    {
        return $this->gift;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money
     */
    public function getPrice(): Money
    {
        return $this->price;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }
}
