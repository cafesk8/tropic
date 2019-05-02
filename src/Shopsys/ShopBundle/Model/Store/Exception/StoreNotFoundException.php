<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Store\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreNotFoundException extends NotFoundHttpException implements StoreException
{
}
