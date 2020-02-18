<?php

declare(strict_types=1);

namespace App\Model\Customer\Exception;

use Shopsys\FrameworkBundle\Model\Order\Exception\OrderException;

class UnsupportedCustomerExportStatusException extends \Exception implements OrderException
{
}
