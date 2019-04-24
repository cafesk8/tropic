<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\GoPay;

use GoPay\Definition\RequestMethods;
use GoPay\GoPay;
use GoPay\Http\JsonBrowser;
use GoPay\Http\Log\NullLogger;
use GoPay\Http\Response;
use GoPay\OAuth2;
use GoPay\Token\CachedOAuth;
use GoPay\Token\InMemoryTokenCache;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency;
use Shopsys\ShopBundle\Model\GoPay\Exception\GoPayPaymentDownloadException;

class GoPayClient
{
    const RESPONSE_STATUS_CODE_OK = '200';

    /**
     * @var array
     */
    private $config;

    /**
     * @var \GoPay\GoPay
     */
    private $goPay;

    /**
     * @var \GoPay\Token\CachedOAuth
     */
    private $oAuth;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        $browser = new JsonBrowser(new NullLogger(), $this->config['timeout']);
        $this->goPay = new GoPay($this->config, $browser);
        $this->oAuth = new CachedOAuth(new OAuth2($this->goPay), new InMemoryTokenCache());
    }

    /**
     * @param string $urlPath
     * @param string $contentType
     * @param string $method
     * @param array|null $data
     * @return \GoPay\Http\Response
     */
    private function sendApiRequest(string $urlPath, string $contentType, string $method, array $data = null): Response
    {
        if ($this->config['goid'] === null) {
            throw new \Shopsys\ShopBundle\Model\GoPay\Exception\GoPayNotConfiguredException();
        }

        $token = $this->oAuth->authorize();

        if ($token->token) {
            return $this->goPay->call(
                $urlPath,
                $contentType,
                'Bearer ' . $token->token,
                $method,
                $data
            );
        }

        return $token->response;
    }

    /**
     * @param array $rawPayment
     * @return \GoPay\Http\Response
     */
    public function sendPaymentToGoPay(array $rawPayment): Response
    {
        $payment = $rawPayment + [
                'target' => [
                    'type' => 'ACCOUNT',
                    'goid' => (string)$this->goPay->getConfig('goid'),
                ],
                'lang' => $this->goPay->getConfig('language'),
            ];

        return $this->sendApiRequest(
            'payments/payment',
            GoPay::JSON,
            RequestMethods::POST,
            $payment
        );
    }

    /**
     * @param int $id
     * @return \GoPay\Http\Response
     */
    public function getStatus(int $id): Response
    {
        $urlPath = 'payments/payment/' . $id;

        $response = $this->sendApiRequest($urlPath, GoPay::FORM, RequestMethods::GET);

        if ($response->statusCode !== self::RESPONSE_STATUS_CODE_OK) {
            throw new GoPayPaymentDownloadException(
                $this->goPay->buildUrl('api/' . $urlPath),
                RequestMethods::GET,
                null,
                $response,
                self::RESPONSE_STATUS_CODE_OK,
                $response->statusCode
            );
        }

        return $response;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency $currency
     * @return array
     */
    public function downloadGoPayPaymentMethodsByCurrency(Currency $currency): array
    {
        $urlPath = 'eshops/eshop/' . $this->goPay->getConfig('goid') . '/payment-instruments/' . $currency->getCode();

        $response = $this->sendApiRequest(
            $urlPath,
            GoPay::FORM,
            RequestMethods::GET
        );

        if ($response->statusCode !== self::RESPONSE_STATUS_CODE_OK) {
            throw new GoPayPaymentDownloadException(
                $this->goPay->buildUrl('api/' . $urlPath),
                RequestMethods::GET,
                null,
                $response,
                self::RESPONSE_STATUS_CODE_OK,
                $response->statusCode
            );
        }

        return $response->json['enabledPaymentInstruments'];
    }

    /**
     * @return string
     */
    public function urlToEmbedJs(): string
    {
        return $this->goPay->buildUrl('gp-gw/js/embed.js');
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->config['language'];
    }
}
