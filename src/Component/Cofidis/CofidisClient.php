<?php

declare(strict_types=1);

namespace App\Component\Cofidis;

use App\Component\Cofidis\Exception\CantConnectToCofidisException;
use App\Component\Cofidis\Exception\CofidisResponseIsNotValidException;

class CofidisClient
{
    private const COFIDIS_TEST_GATEWAY_URL = 'https://test.gw1.iplatba.cz/Service/StartLoanDemand';
    private const COFIDIS_GATEWAY_URL = 'https://gw1.iplatba.cz/Service/StartLoanDemand';

    /**
     * @var array
     */
    private $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param array $paymentData
     * @return string
     */
    public function sendPaymentToCofidis(array $paymentData): string
    {
        $cofidisConnection = curl_init($this->getCofidisGatewayUrl());
        curl_setopt($cofidisConnection, CURLOPT_POST, 1);
        curl_setopt($cofidisConnection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cofidisConnection, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($cofidisConnection, CURLOPT_HEADER, true);
        curl_setopt($cofidisConnection, CURLOPT_CONNECTTIMEOUT, $this->config['timeout']);
        curl_setopt($cofidisConnection, CURLOPT_POSTFIELDS, $paymentData);
        curl_setopt($cofidisConnection, CURLINFO_HEADER_OUT, true);
        if (!$this->config['isProductionMode']) {
            curl_setopt($cofidisConnection, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($cofidisConnection, CURLOPT_SSL_VERIFYHOST, 0);
        }
        $cofidisResult = curl_exec($cofidisConnection);
        if ($cofidisResult === false) {
            throw new CantConnectToCofidisException(sprintf('Cant connect to Cofidis gateway, code=%s, %s"', curl_errno($cofidisConnection), curl_error($cofidisConnection)));
        }
        curl_close($cofidisConnection);
        $responseHeader = CofidisResponseParser::parseCofidisResponseAsArray($cofidisResult);

        if ($this->validateResponseUrl($responseHeader) && $this->validateResponseErrors($responseHeader)) {
            return urldecode($responseHeader['url']);
        }

        throw new CofidisResponseIsNotValidException(sprintf('Cofidis gateway response is not valid! Error code: %s', $responseHeader['error_code']));
    }

    /**
     * @param array $header
     * @return bool
     */
    private function validateResponseUrl(array $header): bool
    {
        return isset($header['url']) && preg_match('/^https%3a%2f%2f(test\.)?gw[1-9]{1}\.iplatba\.cz%2ftampon%3fguid%3d[a-f0-9-]+$/', $header['url'], $result);
    }

    /**
     * @param array $responseHeader
     * @return bool
     */
    private function validateResponseErrors(array $responseHeader): bool
    {
        return (int)$responseHeader['error_code'] === 0;
    }

    /**
     * @return string
     */
    private function getCofidisGatewayUrl(): string
    {
        return $this->config['isProductionMode'] ? self::COFIDIS_GATEWAY_URL : self::COFIDIS_TEST_GATEWAY_URL;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
