<?php

declare(strict_types=1);

namespace App\Component\Balikobot\Shipper\Exception;

use App\Component\Balikobot\Exception\BalikobotException;
use Exception;

class ShipperNotSupportedException extends Exception implements BalikobotException
{
}
