<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Order;

use Shopsys\FrameworkBundle\Component\Money\Money;

class PohodaOrderItem
{
    /**
     * @var string|null
     */
    public ?string $name;

    /**
     * @var string|null
     */
    public ?string $catnum;

    /**
     * @var int|null
     */
    public ?int $quantity;

    /**
     * @var string|null
     */
    public ?string $unit;

    /**
     * @var string|null
     */
    public ?string $vatRate;

    /**
     * @var string|null
     */
    public ?string $vatPercent;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public ?Money $unitPriceWithVat;

    /**
     * @var string|null
     */
    public ?string $pohodaStockId;

    /**
     * @var string|null
     */
    public ?string $pohodaStockName;

    /**
     * @return array
     */
    public function getAsArray(): array
    {
        return [
            'name' => $this->name,
            'catnum' => $this->catnum,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'vatRate' => $this->vatRate,
            'vatPercent' => $this->vatPercent,
            'unitPriceWithVat' => $this->unitPriceWithVat,
            'pohodaStockId' => $this->pohodaStockId,
            'pohodaStockName' => $this->pohodaStockName,
        ];
    }
}
