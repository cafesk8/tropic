<?php

declare(strict_types=1);

namespace App\Model\Order\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderGiftNotFoundException extends NotFoundHttpException
{
}
