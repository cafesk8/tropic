<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Mall\Exception;

class InvalidProductForMallExportException extends \Exception
{
    /**
     * @param string $failReason
     */
    public function __construct(string $failReason)
    {
        parent::__construct('Mall: Product is not ready to mall export for: ' . $failReason, 0, null);
    }
}
