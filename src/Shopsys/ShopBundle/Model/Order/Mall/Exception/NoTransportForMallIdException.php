<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Mall\Exception;

use Exception;

class NoTransportForMallIdException extends Exception implements MallOrderImportException
{
    /**
     * @param string $mallId
     */
    public function __construct(string $mallId)
    {
        $message = sprintf('Transport not found for mall transport id "%s"', $mallId);
        parent::__construct($message);
    }
}
