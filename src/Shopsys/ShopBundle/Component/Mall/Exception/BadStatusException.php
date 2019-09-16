<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Mall\Exception;

use Exception;

class BadStatusException extends Exception implements MallStatusException
{
    /**
     * @param $message
     */
    public function __construct($message)
    {
        parent::__construct($message, 0, null);
    }
}
