<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode\Exception;

use Exception;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\Exception\PromoCodeException;

class PromoCodeAlreadyAppliedException extends Exception implements PromoCodeException
{
    /**
     * @param string $promoCode
     * @param \Exception|null $previous
     */
    public function __construct(string $promoCode, ?Exception $previous = null)
    {
        parent::__construct('Promo code "' . $promoCode . '" is already applied in the current cart.', 0, $previous);
    }
}
