<?php

declare(strict_types=1);

namespace App\Model\Order\Gift;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;

/**
 * @ORM\Entity
 * @ORM\Table(name="order_gifts")
 */
class OrderGift
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Doctrine\Common\Collections\Collection|\App\Model\Product\Product[]
     *
     * @ORM\ManyToMany(targetEntity="App\Model\Product\Product")
     * @ORM\JoinTable(name="order_gift_products")
     */
    private $products;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $enabled;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money
     *
     * @ORM\Column(type="money", precision=20, scale=6)
     */
    private $priceLevelWithVat;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=false)
     */
    private $domainId;

    /**
     * @param \App\Model\Order\Gift\OrderGiftData $orderGiftData
     */
    public function __construct(OrderGiftData $orderGiftData)
    {
        $this->domainId = $orderGiftData->domainId;
        $this->editExceptDomainId($orderGiftData);
    }

    /**
     * @param \App\Model\Order\Gift\OrderGiftData $orderGiftData
     */
    public function edit(OrderGiftData $orderGiftData)
    {
        $this->editExceptDomainId($orderGiftData);
    }

    /**
     * @param \App\Model\Order\Gift\OrderGiftData $orderGiftData
     */
    protected function editExceptDomainId(OrderGiftData $orderGiftData): void
    {
        $this->products = new ArrayCollection($orderGiftData->products);
        $this->enabled = $orderGiftData->enabled;
        $this->priceLevelWithVat = $orderGiftData->priceLevelWithVat;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return \App\Model\Product\Product[]
     */
    public function getProducts()
    {
        return $this->products->toArray();
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money
     */
    public function getPriceLevelWithVat(): Money
    {
        return $this->priceLevelWithVat;
    }

    /**
     * @return int
     */
    public function getDomainId(): int
    {
        return $this->domainId;
    }
}
