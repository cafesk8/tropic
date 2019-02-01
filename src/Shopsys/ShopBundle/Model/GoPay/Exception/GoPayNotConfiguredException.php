<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\GoPay\Exception;

use Exception;

class GoPayNotConfiguredException extends Exception implements GoPayException
{
}
