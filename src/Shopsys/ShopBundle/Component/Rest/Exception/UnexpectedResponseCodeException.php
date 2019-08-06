<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Rest\Exception;

use Exception;

class UnexpectedResponseCodeException extends \Exception implements RestException
{
    /**
     * @param int $receivedCode
     * @param int $expectedCode
     * @param string $methodType
     * @param string $method
     * @param \Exception|null $previous
     */
    public function __construct(
        int $receivedCode,
        int $expectedCode,
        string $methodType,
        string $method,
        ?Exception $previous = null
    ) {
        $message = sprintf(
            'Unexpected response code `%s` was received instead of expected `%s`, `%s` method: %s',
            $receivedCode,
            $expectedCode,
            $methodType,
            $method
        );

        parent::__construct($message, 0, $previous);
    }
}
