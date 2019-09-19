<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Exception;

use Exception;

class ProductIsNotMainVariantException extends Exception
{
    /**
     * @param int $productId
     * @param \Exception|null $previous
     */
    public function __construct($productId, ?Exception $previous = null)
    {
        $message = 'Product with ID ' . $productId . ' is not main variant.';
        parent::__construct($message, 0, $previous);
    }
}
