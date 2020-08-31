<?php

declare(strict_types=1);

namespace App\Model\Order\PromoCode\Exception;

use Exception;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\Exception\PromoCodeException;

class PromoCodeIsNotBetterThanOrderLevelDiscountException extends Exception implements PromoCodeException
{
    /**
     * @param string $invalidPromoCode
     * @param \Exception|null $previous
     */
    public function __construct(string $invalidPromoCode, ?Exception $previous = null)
    {
        parent::__construct(sprintf('Promo code %s does not offer better discount than the current active discount by order level', $invalidPromoCode), 0, $previous);
    }
}
