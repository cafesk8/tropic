<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\PromoProduct;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\ShopBundle\Model\Product\Product;

/**
 * @ORM\Entity
 * @ORM\Table(name="promo_products")
 */
class PromoProduct
{
    /**
     * @var int|null
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $domainId;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Product
     *
     * @ORM\ManyToOne(targetEntity="Shopsys\ShopBundle\Model\Product\Product")
     * @ORM\JoinColumn(name="promo_product_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $product;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money|null
     *
     * @ORM\Column(type="money", precision=20, scale=6, nullable=true)
     */
    private $price;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money|null
     *
     * @ORM\Column(type="money", precision=20, scale=6, nullable=true)
     */
    private $minimalCartPrice;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductData $promoProductData
     */
    public function __construct(PromoProductData $promoProductData)
    {
        $this->domainId = $promoProductData->domainId;
        $this->product = $promoProductData->product;
        $this->price = $promoProductData->price;
        $this->minimalCartPrice = $promoProductData->minimalCartPrice;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductData $promoProductData
     */
    public function edit(PromoProductData $promoProductData)
    {
        $this->domainId = $promoProductData->domainId;
        $this->product = $promoProductData->product;
        $this->price = $promoProductData->price;
        $this->minimalCartPrice = $promoProductData->minimalCartPrice;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getDomainId(): int
    {
        return $this->domainId;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Product\Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getPrice(): ?Money
    {
        return $this->price;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getMinimalCartPrice(): ?Money
    {
        return $this->minimalCartPrice;
    }
}
