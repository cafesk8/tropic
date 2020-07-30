<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer\Exception;

use App\Component\Transfer\Exception\TransferException;
use Exception;

class MainVariantNotFoundInEshopException extends TransferException
{
    /**
     * @param string $productVariantId
     * @param \Exception|null $previous
     */
    public function __construct($productVariantId, ?Exception $previous = null)
    {
        $message = sprintf('There is no main variant for product with variantId %s', $productVariantId);
        parent::__construct($message, 0, $previous);
    }
}
