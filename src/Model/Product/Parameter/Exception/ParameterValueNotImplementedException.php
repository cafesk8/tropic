<?php

declare(strict_types=1);

namespace App\Model\Product\Parameter\Exception;

use Shopsys\FrameworkBundle\Model\Product\Parameter\Exception\ParameterException;

class ParameterValueNotImplementedException extends \Exception implements ParameterException
{
}
