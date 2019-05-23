<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Balikobot\Shipper\Exception;

use Exception;
use Shopsys\ShopBundle\Component\Balikobot\Exception\BalikobotException;

class ShipperNotSupportedException extends Exception implements BalikobotException
{
}
