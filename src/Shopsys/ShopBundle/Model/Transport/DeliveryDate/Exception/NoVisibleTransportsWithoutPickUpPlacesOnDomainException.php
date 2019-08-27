<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport\DeliveryDate\Exception;

use Exception;

class NoVisibleTransportsWithoutPickUpPlacesOnDomainException extends Exception
{
    /**
     * @param \Exception|null $previous
     */
    public function __construct(?Exception $previous = null)
    {
        $message = 'No visible transports without pick up places on domain.';
        parent::__construct($message, 0, $previous);
    }
}
