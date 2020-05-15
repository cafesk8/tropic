<?php

declare(strict_types=1);

namespace App\Model\Product\Flag;

use App\Model\Product\Product;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="product_flags")
 * @ORM\Entity
 */
class ProductFlag
{
    /**
     * @var \App\Model\Product\Product
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\Model\Product\Product", inversedBy="flags")
     * @ORM\JoinColumn(nullable=false)
     */
    private $product;

    /**
     * @var \App\Model\Product\Flag\Flag
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\Model\Product\Flag\Flag")
     * @ORM\JoinColumn(nullable=false)
     */
    private $flag;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="date", nullable=true)
     */
    private $activeFrom;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="date", nullable=true)
     */
    private $activeTo;

    /**
     * @param \App\Model\Product\Flag\ProductFlagData $productFlagData
     */
    final public function __construct(ProductFlagData $productFlagData)
    {
        $this->product = $productFlagData->product;
        $this->flag = $productFlagData->flag;
        $this->activeFrom = $productFlagData->activeFrom;
        $this->activeTo = $productFlagData->activeTo;
    }

    /**
     * @param \App\Model\Product\Flag\ProductFlagData $productFlagData
     * @return \App\Model\Product\Flag\ProductFlag
     */
    public static function create(ProductFlagData $productFlagData): self
    {
        return new static($productFlagData);
    }

    /**
     * @return \App\Model\Product\Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @return \App\Model\Product\Flag\Flag
     */
    public function getFlag(): Flag
    {
        return $this->flag;
    }

    /**
     * @return \DateTime|null
     */
    public function getActiveFrom(): ?DateTime
    {
        return $this->activeFrom;
    }

    /**
     * @return \DateTime|null
     */
    public function getActiveTo(): ?DateTime
    {
        return $this->activeTo;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return ($this->activeFrom === null || $this->activeFrom->getTimestamp() < time()) && ($this->activeTo === null || $this->activeTo->getTimestamp() > time());
    }
}
