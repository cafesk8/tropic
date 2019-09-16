<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Mall\Exception;

use Exception;

class StatusChangException extends Exception implements MallOrderImportException
{
    /**
     * @param \Exception $exception
     */
    public function __construct(Exception $exception)
    {
        parent::__construct($exception->getMessage(), $exception->getCode(), $exception);
    }
}
