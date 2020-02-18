<?php

declare(strict_types=1);

namespace App\Component\Domain\Exception;

use Exception;
use Shopsys\FrameworkBundle\Component\Domain\Exception\DomainException;

class MissingDomainNameException extends Exception implements DomainException
{
    /**
     * @param string $message
     * @param \Exception|null $previous
     */
    public function __construct($message = '', $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
