<?php

declare(strict_types=1);

namespace App\Component\Mall\Exception;

use Exception;

class BadStatusException extends Exception implements MallStatusException
{
    /**
     * @param string $message
     */
    public function __construct($message)
    {
        parent::__construct($message, 0, null);
    }
}
