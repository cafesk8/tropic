<?php

declare(strict_types=1);

namespace App\Model\Product\Pricing\Exception;

use Exception;
use Shopsys\FrameworkBundle\Model\Pricing\Exception\PricingException;

class PriceLessThanZeroException extends Exception implements PricingException
{
}
