<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\PayPal\Exception;

use Exception;
use Shopsys\ShopBundle\Model\PayPal\PayPalClient;

class UnsupportedPayPalModeException extends Exception
{
    public function __construct()
    {
        parent::__construct(
            sprintf(
                'Use only %s or %s mode for payPalMode in PayPal parameters in parameters.yml',
                PayPalClient::MODE_SANDBOX,
                PayPalClient::MODE_LIVE
            )
        );
    }
}
