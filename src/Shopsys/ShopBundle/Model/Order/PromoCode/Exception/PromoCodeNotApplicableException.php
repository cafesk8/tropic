<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode\Exception;

use Exception;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\Exception\PromoCodeException;

class PromoCodeNotApplicableException extends Exception implements PromoCodeException
{
    /**
     * @param string $invalidPromoCode
     * @param \Exception|null $previous
     */
    public function __construct(string $invalidPromoCode, ?Exception $previous = null)
    {
        parent::__construct('Promo Code ' . $invalidPromoCode . ' is not applicable to any products currently in cart', 0, $previous);
    }
}
