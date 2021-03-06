<?php

declare(strict_types=1);

namespace App\Model\Cart\Exception;

use Exception;
use Shopsys\FrameworkBundle\Model\Cart\Exception\CartException;

class OutOfStockException extends Exception implements CartException
{
}
