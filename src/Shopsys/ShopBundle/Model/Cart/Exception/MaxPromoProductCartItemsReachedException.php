<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Cart\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MaxPromoProductCartItemsReachedException extends BadRequestHttpException
{
}
