<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Balikobot\Exception;

use Exception;

class UnexpectedResponseException extends Exception implements BalikobotException
{
}