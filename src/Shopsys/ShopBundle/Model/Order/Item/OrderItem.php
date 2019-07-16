<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Item;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Order\Item\Exception\MainVariantCannotBeOrderedException;
use Shopsys\FrameworkBundle\Model\Order\Item\Exception\WrongItemTypeException;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderItem as BaseOrderItem;
use Shopsys\FrameworkBundle\Model\Order\Order as BaseOrder;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Product;

/**
 * @ORM\Table(name="order_items")
 * @ORM\Entity
 */
class OrderItem extends BaseOrderItem
{
    public const TYPE_PROMO_CODE = 'promo_code';

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
            if ($item->isTypePromoCode() && $item->getProduct() === $this->getProduct()) {
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

    protected function checkTypeProductOrPromoCode(): void
    {
        if (!$this->isTypeProduct() && !$this->isTypePromoCode()) {
            throw new WrongItemTypeException(self::TYPE_PRODUCT, $this->type);
        }
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\Product|null
     */
    public function getProduct(): ?Product
    {
        $this->checkTypeProductOrPromoCode();
        return $this->product;
    }

    /**
     * @return bool
     */
    public function hasProduct(): bool
    {
        $this->checkTypeProductOrPromoCode();
        return $this->product !== null;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product|null $product
     */
    public function setProduct(?Product $product): void
    {
        $this->checkTypeProductOrPromoCode();

        if ($product !== null && $product->isMainVariant()) {
            throw new MainVariantCannotBeOrderedException();
        }

        $this->product = $product;
    }
}
