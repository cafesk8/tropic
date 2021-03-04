<?php

declare(strict_types=1);

namespace App\Model\Order\Item;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Order\Item\Exception\WrongItemTypeException;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItem as BaseOrderItem;
use Shopsys\FrameworkBundle\Model\Order\Order;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Product;

/**
 * @ORM\Table(name="order_items")
 * @ORM\Entity
 * @property \App\Model\Order\Order $order
 * @property \App\Model\Transport\Transport|null $transport
 * @property \App\Model\Payment\Payment|null $payment
 * @property \App\Model\Product\Product|null $product
 * @method \App\Model\Order\Order getOrder()
 * @method edit(\App\Model\Order\Item\OrderItemData $orderItemData)
 * @method setTransport(\App\Model\Transport\Transport $transport)
 * @method \App\Model\Transport\Transport getTransport()
 * @method setPayment(\App\Model\Payment\Payment $payment)
 * @method \App\Model\Payment\Payment getPayment()
 * @method \App\Model\Product\Product|null getProduct()
 * @method setProduct(\App\Model\Product\Product|null $product)
 */
class OrderItem extends BaseOrderItem
{
    public const TYPE_GIFT_CERTIFICATE = 'gift_certificate';

    public const TYPE_PROMO_CODE = 'promo_code';

    public const TYPE_GIFT = 'gift';

    public const TYPE_ORDER_DISCOUNT_LEVEL = 'order_discount_level';

    public const TYPE_TRANSPORT_FEE = 'transport_fee';

    /**
     * @var \App\Model\Order\Item\OrderItem|null
     *
     * @ORM\ManyToOne(targetEntity="App\Model\Order\Item\OrderItem")
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
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $preparedQuantity;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $saleItem;

    /**
     * @param \App\Model\Order\Order $order
     * @param string $name
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price $price
     * @param string $vatPercent
     * @param int $quantity
     * @param string $type
     * @param string|null $unitName
     * @param string|null $catnum
     * @param int|null $preparedQuantity
     * @param bool $saleItem
     */
    public function __construct(
        Order $order,
        string $name,
        Price $price,
        string $vatPercent,
        int $quantity,
        string $type,
        ?string $unitName,
        ?string $catnum,
        ?int $preparedQuantity = 0,
        bool $saleItem = false
    ) {
        parent::__construct($order, $name, $price, $vatPercent, $quantity, $type, $unitName, $catnum);

        $this->saleItem = $saleItem;
        $this->preparedQuantity = $preparedQuantity;
    }

    /**
     * @param \App\Model\Product\Product $product
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
     * @return \App\Model\Product\Product|null
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
     * @return \App\Model\Order\Item\OrderItem|null
     */
    public function getPromoCodeForOrderItem(): ?self
    {
        /** @var \App\Model\Order\Item\OrderItem $item */
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
     * @return \App\Model\Order\Item\OrderItem|null
     */
    public function getMainOrderItem(): ?self
    {
        return $this->mainOrderItem;
    }

    /**
     * @param \App\Model\Order\Item\OrderItem|null $mainOrderItem
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

    /**
     * @return int
     */
    public function getPreparedQuantity(): int
    {
        return $this->preparedQuantity;
    }

    /**
     * @param int $preparedQuantity
     */
    public function setPreparedQuantity(int $preparedQuantity): void
    {
        $this->preparedQuantity = $preparedQuantity;
    }

    /**
     * @return bool
     */
    public function isSaleItem(): bool
    {
        return $this->saleItem;
    }

    /**
     * @return bool
     */
    public function isTypeOrderDiscount(): bool
    {
        return $this->type === self::TYPE_ORDER_DISCOUNT_LEVEL;
    }
}
