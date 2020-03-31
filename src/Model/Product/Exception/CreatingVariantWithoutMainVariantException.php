<?php

declare(strict_types=1);

namespace App\Model\Product\Exception;

use Exception;
use Shopsys\FrameworkBundle\Model\Product\Exception\ProductException;

class CreatingVariantWithoutMainVariantException extends Exception implements ProductException
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
