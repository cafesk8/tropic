<?php

declare(strict_types=1);

namespace App\Model\WatchDog;

use App\Model\Pricing\Group\PricingGroup;
use App\Model\Product\Product;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Component\Money\Money;

/**
 * @ORM\Table(name="watch_dogs")
 * @ORM\Entity
 */
class WatchDog
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
     * @var \DateTime
     *
     * @ORM\Column(type="date")
     */
    private $createdAt;

    /**
     * @var \App\Model\Product\Product
     *
     * @ORM\ManyToOne(targetEntity="App\Model\Product\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private $product;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $email;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $availabilityWatcher;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $priceWatcher;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money
     *
     * @ORM\Column(type="money", precision=20, scale=6, nullable=false)
     */
    private $originalPrice;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money|null
     *
     * @ORM\Column(type="money", precision=20, scale=6, nullable=true)
     */
    private $targetedDiscount;

    /**
     * @var \App\Model\Pricing\Group\PricingGroup
     *
     * @ORM\ManyToOne(targetEntity="App\Model\Pricing\Group\PricingGroup")
     * @ORM\JoinColumn(name="pricing_group_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    private $pricingGroup;

    /**
     * @param \App\Model\WatchDog\WatchDogData $watchDogData
     */
    public function __construct(WatchDogData $watchDogData)
    {
        $this->product = $watchDogData->product;
        $this->createdAt = $watchDogData->createdAt;
        $this->email = $watchDogData->email;
        $this->availabilityWatcher = $watchDogData->availabilityWatcher;
        $this->priceWatcher = $watchDogData->priceWatcher;
        $this->originalPrice = $watchDogData->originalPrice;
        $this->targetedDiscount = $watchDogData->targetedDiscount;
        $this->pricingGroup = $watchDogData->pricingGroup;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return \App\Model\Product\Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return bool
     */
    public function isAvailabilityWatcher(): bool
    {
        return $this->availabilityWatcher;
    }

    /**
     * @return bool
     */
    public function isPriceWatcher(): bool
    {
        return $this->priceWatcher;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money
     */
    public function getOriginalPrice(): Money
    {
        return $this->originalPrice;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getTargetedDiscount(): ?Money
    {
        return $this->targetedDiscount;
    }

    /**
     * @return \App\Model\Pricing\Group\PricingGroup
     */
    public function getPricingGroup(): PricingGroup
    {
        return $this->pricingGroup;
    }
}
