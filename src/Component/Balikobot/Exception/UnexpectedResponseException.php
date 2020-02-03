<?php

declare(strict_types=1);

namespace App\Component\Balikobot\Exception;

use Exception;

class UnexpectedResponseException extends Exception implements BalikobotException
{
}
