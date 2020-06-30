<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Order;

class PohodaCurrency
{
    private const CODE_EUR = 'EUR';

    /**
     * @var string
     */
    public string $code;

    /**
     * @return bool
     */
    public function isEur(): bool
    {
        return $this->code === self::CODE_EUR;
    }
}
