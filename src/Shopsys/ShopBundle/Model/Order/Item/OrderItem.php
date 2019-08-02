<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Item;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItem as BaseOrderItem;
use Shopsys\FrameworkBundle\Model\Order\Order as BaseOrder;
use Shopsys\FrameworkBundle\Model\Pricing\Price;

/**
 * @ORM\Table(name="order_items")
 * @ORM\Entity
 */
class OrderItem extends BaseOrderItem
{
    public const TYPE_PROMO_CODE = 'promo_code';

    /**
     * @var \Shopsys\ShopBundle\Model\Order\Item\OrderItem|null
     *
     * @ORM\ManyToOne(targetEntity="Shopsys\ShopBundle\Model\Order\Item\OrderItem")
     * @ORM\JoinColumn(name="main_order_item_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private $mainOrderItem;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $ean;

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param string $name
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $price
     * @param string $vatPercent
     * @param int $quantity
     * @param string $type
     * @param null|string $unitName
     * @param null|string $catnum
     */
    public function __construct(
        BaseOrder $order,
        string $name,
        Price $price,
        string $vatPercent,
        int $quantity,
        string $type,
        ?string $unitName,
        ?string $catnum
    ) {
        parent::__construct(
            $order,
            $name,
            $price,
            $vatPercent,
            $quantity,
            $type,
            $unitName,
            $catnum
        );
    }

    /**
     * @return string|null
     */
    public function getEan(): ?string
    {
        return $this->ean;
    }

    /**
     * @param string|null $ean
     */
    public function setEan(?string $ean): void
    {
        $this->ean = $ean;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Order\Item\OrderItem|null
     */
    public function getPromoCodeForOrderItem(): ?self
    {
        /** @var \Shopsys\ShopBundle\Model\Order\Item\OrderItem $item */
        foreach ($this->getOrder()->getItems() as $item) {
            if ($item->isTypePromoCode() && $item->getMainOrderItem() === $this) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isTypePromoCode(): bool
    {
        return $this->type === self::TYPE_PROMO_CODE;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Order\Item\OrderItem|null
     */
    public function getMainOrderItem(): ?self
    {
        return $this->mainOrderItem;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Item\OrderItem|null $mainOrderItem
     */
    public function setMainOrderItem(?self $mainOrderItem): void
    {
        $this->mainOrderItem = $mainOrderItem;
    }
}
