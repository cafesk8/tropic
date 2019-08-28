<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Parameter\Exception;

use Exception;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Exception\ParameterException;

class InvalidParameterTypeException extends Exception implements ParameterException
{
}
