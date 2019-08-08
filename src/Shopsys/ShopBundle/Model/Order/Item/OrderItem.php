<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Item;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Order\Item\Exception\WrongItemTypeException;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItem as BaseOrderItem;
use Shopsys\FrameworkBundle\Model\Product\Product;

/**
 * @ORM\Table(name="order_items")
 * @ORM\Entity
 */
class OrderItem extends BaseOrderItem
{
    public const TYPE_GIFT_CERTIFICATE = 'gift_certificate';

    public const TYPE_PROMO_CODE = 'promo_code';

    public const TYPE_GIFT = 'gift';

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
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     */
    public function setGift(Product $product): void
    {
        $this->checkTypeGift();
        $this->product = $product;
    }

    protected function checkTypeGift(): void
    {
        if (!$this->isTypeGift()) {
            throw new WrongItemTypeException(self::TYPE_GIFT, $this->type);
        }
    }

    /**
     * @return bool
     */
    public function isTypeGift(): bool
    {
        return $this->type === self::TYPE_GIFT;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\Product|null
     */
    public function getGift(): ?Product
    {
        $this->checkTypeGift();
        return $this->product;
    }

    protected function checkTypeProduct(): void
    {
        if ($this->isTypeGift()) {
            return;
        }

        parent::checkTypeProduct();
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

    /**
     * @return bool
     */
    public function isTypeGiftCertification(): bool
    {
        return $this->type === self::TYPE_GIFT_CERTIFICATE;
    }
}
