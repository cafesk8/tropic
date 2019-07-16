<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\Product\Pricing\Exception;

use Exception;
use Shopsys\FrameworkBundle\Model\Pricing\Exception\PricingException;

class PriceLessThanZeroException extends Exception implements PricingException
{
}
