<?php

declare(strict_types=1);

namespace App\Component\Balikobot\Shipper\Exception;

use Exception;
use App\Component\Balikobot\Exception\BalikobotException;

class ShipperNotSupportedException extends Exception implements BalikobotException
{
}
