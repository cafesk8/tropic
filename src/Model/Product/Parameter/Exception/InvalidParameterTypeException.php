<?php

declare(strict_types=1);

namespace App\Model\Product\Parameter\Exception;

use Exception;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Exception\ParameterException;

class InvalidParameterTypeException extends Exception implements ParameterException
{
}
