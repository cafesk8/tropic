<?php

declare(strict_types=1);

namespace App\Model\Cart\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MaxPromoProductCartItemsReachedException extends BadRequestHttpException
{
}
