<?php

declare(strict_types=1);

namespace App\Component\LuigisBox;

use App\Model\LuigisBox\LuigisBoxObject;
use App\Model\LuigisBox\LuigisBoxObjectCollection;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;

class LuigisBoxClient
{
    private const BASE_URL = 'https://live.luigisbox.com';
    private const CONTENT_TYPE = 'application/json; charset=utf-8';
    private const ENDPOINT = '/v1/content';

    private Client $client;

    private LuigisBoxApiKeysProvider $keysProvider;

    /**
     * @param \App\Component\LuigisBox\LuigisBoxApiKeysProvider $keysProvider
     */
    public function __construct(LuigisBoxApiKeysProvider $keysProvider)
    {
        $this->client = new Client();
        $this->keysProvider = $keysProvider;
    }

    /**
     * @param \App\Model\LuigisBox\LuigisBoxObjectCollection $content
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     */
    public function update(LuigisBoxObjectCollection $content, DomainConfig $domainConfig): void
    {
        $this->request('POST', $content, $domainConfig);
    }

    /**
     * @param \App\Model\LuigisBox\LuigisBoxObjectCollection $content
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     */
    public function remove(LuigisBoxObjectCollection $content, DomainConfig $domainConfig): void
    {
        $this->request('DELETE', $content, $domainConfig);
    }

    /**
     * @param string $method
     * @param \App\Model\LuigisBox\LuigisBoxObjectCollection $data
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function request(string $method, LuigisBoxObjectCollection $data, DomainConfig $domainConfig): ResponseInterface
    {
        $locale = $domainConfig->getLocale();
        $date = gmdate('D, d M Y H:i:s T');
        try {
            return $this->client->request($method, self::BASE_URL . self::ENDPOINT, [
                'headers' => [
                    'Content-Type' => self::CONTENT_TYPE,
                    'date' => $date,
                    'Authorization' => 'guzzle ' . $this->keysProvider->getPublicKey($locale) . ':' . $this->digest($method, $date, $this->keysProvider->getPrivateKey($locale)),
                ],
                'body' => json_encode($data),
            ]);
        } catch (RequestException $exception) {
            $response = $exception->getResponse();

            throw new LuigisBoxClientException(
                'HTTP ' . $response->getStatusCode() . ': ' . $response->getReasonPhrase(),
                array_map(fn (LuigisBoxObject $object) => $object->web_url, $data->toArray())
            );
        }
    }

    /**
     * @param string $method
     * @param string $date
     * @param string $privateKey
     * @return string
     */
    private function digest(string $method, string $date, string $privateKey): string
    {
        return trim(base64_encode(hash_hmac(
            'sha256',
            $method . "\n" . self::CONTENT_TYPE . "\n" . $date . "\n" . self::ENDPOINT,
            $privateKey,
            true
        )));
    }
}