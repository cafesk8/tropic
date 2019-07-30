<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Cart\Exception;

use Exception;
use Shopsys\FrameworkBundle\Model\Cart\Exception\CartException;

class OutOfStockException extends Exception implements CartException
{
}
