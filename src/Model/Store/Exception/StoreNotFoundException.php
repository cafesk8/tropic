<?php

declare(strict_types=1);

namespace App\Model\Store\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreNotFoundException extends NotFoundHttpException implements StoreException
{
}
