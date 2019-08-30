<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport\DeliveryDate\Exception;

use Exception;

class DeliveryDateLocaleNotSupportedException extends Exception
{
    /**
     * @param string $locale
     * @param \Exception|null $previous
     */
    public function __construct(string $locale, ?Exception $previous = null)
    {
        $message = 'Locale "' . $locale . '" is not supported.';
        parent::__construct($message, 0, $previous);
    }
}
