<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Balikobot;

use Shopsys\ShopBundle\Component\Balikobot\Exception\UnexpectedResponseException;

class BalikobotClient
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $apiUrl = 'https://api.balikobot.cz';

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->username = $config['username'];
        $this->apiKey = $config['apiKey'];
    }

    /**
     * @param string $method
     * @param string $shipper
     * @param array $data
     * @param string|null $url
     * @return array
     */
    public function request(string $method, string $shipper, array $data = [], ?string $url = null): array
    {
        $handle = curl_init();

        $requestUrl = $this->getUrl($method, $shipper, $url);

        curl_setopt($handle, CURLOPT_URL, $requestUrl);

        curl_setopt($handle, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $this->getToken(),
            'Content-Type: application/json',
        ]);

        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($handle, CURLOPT_TIMEOUT, 100);

        curl_setopt($handle, CURLOPT_FAILONERROR, false);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_HEADER, false);

        if (count($data) > 0) {
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($handle);
        curl_close($handle);

        if ($response === false) {
            throw new UnexpectedResponseException(
                sprintf('Response was not received from URL: %s, Method: %s, Shipper: %s', $requestUrl, $method, $shipper)
            );
        }

        return json_decode($response, true);
    }

    /**
     * @return string
     */
    private function getToken(): string
    {
        return base64_encode($this->username . ':' . $this->apiKey);
    }

    /**
     * @param string $method
     * @param string $shipper
     * @param string|null $url
     * @return string
     */
    private function getUrl(string $method, string $shipper, ?string $url): string
    {
        $finalUrl = $this->apiUrl;
        $finalUrl .= '/' . $shipper;
        $finalUrl .= '/' . $method;

        if ($url !== null) {
            $finalUrl .= '/' . $url;
        }

        return $finalUrl;
    }
}
