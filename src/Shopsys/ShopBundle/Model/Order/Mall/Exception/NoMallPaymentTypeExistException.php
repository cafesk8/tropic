<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Mall\Exception;

use Exception;

class NoMallPaymentTypeExistException extends Exception implements MallOrderImportException
{
    public function __construct()
    {
        $message = 'No mall payment type exist. Please create it in administration.';
        parent::__construct($message);
    }
}
