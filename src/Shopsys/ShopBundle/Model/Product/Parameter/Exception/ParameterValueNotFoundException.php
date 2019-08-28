<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Parameter\Exception;

use Shopsys\FrameworkBundle\Model\Product\Parameter\Exception\ParameterException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ParameterValueNotFoundException extends NotFoundHttpException implements ParameterException
{
}
