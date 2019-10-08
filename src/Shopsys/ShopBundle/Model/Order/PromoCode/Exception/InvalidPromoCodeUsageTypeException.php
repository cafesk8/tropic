<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode\Exception;

use Exception;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\Exception\PromoCodeException;

class InvalidPromoCodeUsageTypeException extends Exception implements PromoCodeException
{
}
