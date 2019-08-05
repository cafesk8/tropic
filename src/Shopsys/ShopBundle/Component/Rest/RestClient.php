<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Rest;

use Shopsys\ShopBundle\Component\Rest\Exception\UnexpectedResponseCodeException;
use Shopsys\ShopBundle\Component\Rest\Exception\UnexpectedResponseException;

class RestClient
{
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_PUT = 'PUT';

    public const EXPECTED_CODE_GET = 200;
    public const EXPECTED_CODE_POST = 201;
    public const EXPECTED_CODE_DELETE = 204;
    public const EXPECTED_CODE_PUT = 200;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var int
     */
    private $connectionTimeout;

    /**
     * @param string $host
     * @param string $username
     * @param string $password
     * @param int $timeout
     * @param int $connectionTimeout
     */
    public function __construct(
        string $host,
        string $username,
        string $password,
        int $timeout = 600,
        int $connectionTimeout = 120
    ) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->timeout = $timeout;
        $this->connectionTimeout = $connectionTimeout;
    }

    /**
     * @param string $url
     * @return \Shopsys\ShopBundle\Component\Rest\RestResponse
     */
    public function get(string $url): RestResponse
    {
        $response = $this->request(self::METHOD_GET, $url);

        if ($response->getCode() !== self::EXPECTED_CODE_GET) {
            throw new UnexpectedResponseCodeException($response->getCode(), self::EXPECTED_CODE_GET, self::METHOD_GET, $url);
        }

        return $response;
    }

    /**
     * @param string $url
     * @param string[] $requestData
     * @return \Shopsys\ShopBundle\Component\Rest\RestResponse
     */
    public function post(string $url, array $requestData): RestResponse
    {
        $response = $this->request(self::METHOD_POST, $url, $requestData);

        if (in_array($response->getCode(), [self::EXPECTED_CODE_POST, self::EXPECTED_CODE_GET], true) === false) {
            throw new UnexpectedResponseCodeException($response->getCode(), self::EXPECTED_CODE_GET, self::METHOD_POST, $url);
        }

        return $response;
    }

    /**
     * @param string $url
     * @param string[] $requestData
     * @return \Shopsys\ShopBundle\Component\Rest\RestResponse
     */
    public function put(string $url, $requestData): RestResponse
    {
        $response = $this->request(self::METHOD_PUT, $url, $requestData);

        if ($response->getCode() !== self::EXPECTED_CODE_PUT) {
            throw new UnexpectedResponseCodeException($response->getCode(), self::EXPECTED_CODE_GET, self::METHOD_PUT, $url);
        }

        return $response;
    }

    /**
     * @param string $url
     * @return \Shopsys\ShopBundle\Component\Rest\RestResponse
     */
    public function delete(string $url): RestResponse
    {
        $response = $this->request(self::METHOD_DELETE, $url);

        if ($response->getCode() !== self::EXPECTED_CODE_DELETE) {
            throw new UnexpectedResponseCodeException($response->getCode(), self::EXPECTED_CODE_GET, self::METHOD_DELETE, $url);
        }

        return $response;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $data
     * @return \Shopsys\ShopBundle\Component\Rest\RestResponse
     */
    private function request(string $method, string $url, $data = []): RestResponse
    {
        $handle = curl_init();
        $headers = [];
        $headers[] = 'Authorization: Basic ' . $this->getToken();
        $headers[] = 'Cache-Control: no-cache';

        $apiUrl = $this->host . '/' . ltrim($url, '/');
        curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($handle, CURLOPT_URL, $apiUrl);

        if ($method === self::METHOD_POST || $method === self::METHOD_PUT) {
            $fields = json_encode($data);
            $fields = str_replace('\u200b', '', $fields);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($fields);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $fields);
        }

        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($handle, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_ENCODING, '');
        curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        $response = curl_exec($handle);

        if ($response === false) {
            throw new UnexpectedResponseException(
                sprintf('Response was not received from URL: %s, Method: %s', $apiUrl, $method)
            );
        }

        $responseCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        curl_close($handle);

        return new RestResponse($responseCode, json_decode($response, true));
    }

    /**
     * @return string
     */
    private function getToken(): string
    {
        return base64_encode($this->username . ':' . $this->password);
    }
}
