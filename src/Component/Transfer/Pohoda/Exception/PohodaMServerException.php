<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Exception;

use Exception;

class PohodaMServerException extends Exception implements PohodaTransferExceptionInterface
{
    /**
     * @param string $errorMessage
     * @param \Exception|null $previous
     */
    public function __construct(
        string $errorMessage,
        ?Exception $previous = null
    ) {
        parent::__construct($errorMessage, 0, $previous);
    }
}
