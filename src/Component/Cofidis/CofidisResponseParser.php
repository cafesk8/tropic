<?php

declare(strict_types=1);

namespace App\Component\Cofidis;

class CofidisResponseParser
{
    /**
     * @see \Tests\App\Unit\Component\Cofidis\CofidisResponseParserTest
     * @param string $response
     * @return array
     */
    public static function parseCofidisResponseAsArray(string $response): array
    {
        $arrayResponse = [];
        $responseFields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $response));
        foreach ($responseFields as $field) {
            if (preg_match('/([^:]+): (.+)/m', $field, $match)) {
                $arrayResponse[strtolower($match[1])] = trim($match[2]);
            }
        }
        return $arrayResponse;
    }
}
