<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Exception;

use Shopsys\FrameworkBundle\Model\Order\Exception\OrderException;

class UnsupportedOrderExportStatusException extends \Exception implements OrderException
{
}
