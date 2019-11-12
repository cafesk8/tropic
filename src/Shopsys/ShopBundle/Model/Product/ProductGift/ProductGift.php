<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\ProductGift;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Product\Product;

/**
 * @ORM\Entity
 * @ORM\Table(name="product_gifts")
 */
class ProductGift
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
     * @var \Shopsys\FrameworkBundle\Model\Product\Product
     *
     * @ORM\ManyToOne(targetEntity="Shopsys\ShopBundle\Model\Product\Product")
     * @ORM\JoinColumn(name="gift_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $gift;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\Shopsys\ShopBundle\Model\Product\Product[]
     *
     * @ORM\ManyToMany(targetEntity="Shopsys\ShopBundle\Model\Product\Product", inversedBy="productGifts", cascade={"persist"}, fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="product_gift_products")
     */
    private $products;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $domainId;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $active;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $title;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductGift\ProductGiftData $productGiftData
     */
    public function __construct(ProductGiftData $productGiftData)
    {
        $this->gift = $productGiftData->gift;
        $this->products = $productGiftData->products;
        $this->domainId = $productGiftData->domainId;
        $this->active = (bool)$productGiftData->active;
        $this->title = $productGiftData->title;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductGift\ProductGiftData $productGiftData
     */
    public function edit(ProductGiftData $productGiftData)
    {
        $this->gift = $productGiftData->gift;
        $this->products = $productGiftData->products;
        $this->domainId = $productGiftData->domainId;
        $this->active = (bool)$productGiftData->active;
        $this->title = $productGiftData->title;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Product\Product
     */
    public function getGift(): Product
    {
        return $this->gift;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getProducts(): array
    {
        return $this->products->toArray();
    }

    /**
     * @return int
     */
    public function getDomainId(): int
    {
        return $this->domainId;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }
}
