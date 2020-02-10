<?php

declare(strict_types=1);

namespace App\Model\PayPal\Exception;

use App\Model\PayPal\PayPalClient;
use Exception;

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
