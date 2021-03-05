<?php

declare(strict_types=1);

namespace App\Model\Order\Item;

use Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedProduct as BaseQuantifiedProduct;
use Shopsys\FrameworkBundle\Model\Product\Product;

/**
 * @method \App\Model\Product\Product getProduct()
 */
class QuantifiedProduct extends BaseQuantifiedProduct
{
    /**
     * @var bool
     */
    private $saleItem;

    /**
     * @param \App\Model\Product\Product $product
     * @param mixed $quantity
     * @param bool $saleItem
     */
    public function __construct(Product $product, $quantity, bool $saleItem = false)
    {
        parent::__construct($product, $quantity);
        $this->saleItem = $saleItem;
    }

    /**
     * @return bool
     */
    public function isSaleItem(): bool
    {
        return $this->saleItem;
    }

    /**
     * @param string|null $locale
     * @return string|null
     */
    public function getName(?string $locale = null): ?string
    {
        $name = $this->product->getName($locale);
        if ($name !== null && $this->isSaleItem()) {
            return sprintf('%s - %s', $name, t('Výprodej'));
        }

        return $name;
    }
}
