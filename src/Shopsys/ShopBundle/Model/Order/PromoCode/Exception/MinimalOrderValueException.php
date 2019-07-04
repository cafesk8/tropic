<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode\Exception;

use Exception;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\Exception\PromoCodeException;

class MinimalOrderValueException extends Exception implements PromoCodeException
{
    /**
     * @param string $enteredCode
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $minimalOrderValue
     * @param \Exception|null $previous
     */
    public function __construct(string $enteredCode, ?Money $minimalOrderValue, ?Exception $previous = null)
    {
        parent::__construct('Minimal order price for promo code "' . $enteredCode . '" is ' . $minimalOrderValue->getAmount(), 0, $previous);
    }
}
