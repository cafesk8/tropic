<?php

declare(strict_types=1);

namespace App\Model\Order\PromoCode\Exception;

use Exception;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\Exception\PromoCodeException;

class InactivePromoCodeException extends Exception implements PromoCodeException
{
    /**
     * @param string $invalidPromoCode
     * @param \Exception|null $previous
     */
    public function __construct(string $invalidPromoCode, ?Exception $previous = null)
    {
        parent::__construct('Promo code "' . $invalidPromoCode . '" is not active.', 0, $previous);
    }
}
