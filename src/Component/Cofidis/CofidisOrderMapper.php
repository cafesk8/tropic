<?php

declare(strict_types=1);

namespace App\Component\Cofidis;

use App\Model\Order\Order;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Router\DomainRouterFactory;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CofidisOrderMapper
{
    private const PRODUCT_IDS_MAX_LENGTH = 50;
    private const PRODUCT_NAMES_MAX_LENGTH = 255;

    private CofidisSignatureFacade $cofidisSignatureFacade;

    private DomainRouterFactory $domainRouterFactory;

    private Domain $domain;

    /**
     * @param \App\Component\Cofidis\CofidisSignatureFacade $cofidisSignatureFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Component\Router\DomainRouterFactory $domainRouterFactory
     */
    public function __construct(
        CofidisSignatureFacade $cofidisSignatureFacade,
        Domain $domain,
        DomainRouterFactory $domainRouterFactory
    ) {
        $this->cofidisSignatureFacade = $cofidisSignatureFacade;
        $this->domain = $domain;
        $this->domainRouterFactory = $domainRouterFactory;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param array $config
     * @return array
     */
    public function createCofidisPaymentData(Order $order, array $config): array
    {
        $router = $this->domainRouterFactory->getRouter($order->getDomainId());
        $paymentData = [
            'valid_until' => date('Ymd\THis', strtotime('+ 1 day')),
            'seed' => mb_substr(uniqid('', true), 0, 20, 'UTF-8'),
            'cofisun_pwd' => $config['cofisun_pass'],
            'operation' => 'START_LOAN_DEMAND',
            'transaction_id' => $order->getId(),
            'additional_data' => $this->getAdditionData($order),
            'amount' => (int)(ceil($order->getTotalPriceWithVat()->getAmount()) * 100),
            'deposit' => 0,
            'num_installments' => 0,
            'currency' => 'CZK',
            'session_id' => '',
            'product_id' => $this->getProductIds($order),
            'product_description' => $this->getProductNames($order),
            'merchant_id' => (string)$config['merchant_id'],
            'login' => $config['login'],
            'last_name' => $order->getLastName(),
            'first_name' => $order->getFirstName(),
            'mobilephone' => $order->getTelephone(),
            'fixphone' => '',
            'email' => $order->getEmail(),
            'pri_city' => $order->getDeliveryCity(),
            'pri_street' => $order->getDeliveryStreet(),
            'pri_number' => '',
            'pri_orientation_num' => '',
            'pri_zip' => $this->getPreparedPostcode($order->getDeliveryPostcode()),
            'con_city' => '',
            'con_street' => '',
            'con_number' => '',
            'con_orientation_num' => '',
            'con_zip' => '',
            'url' => $router->generate(
                'front_order_paid',
                ['urlHash' => $order->getUrlHash()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ];
        $paymentData['signature'] = $this->cofidisSignatureFacade->getSignature($paymentData, $config['inbound_pass']);

        return $paymentData;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @return string
     */
    private function getAdditionData(Order $order): string
    {
        $transport = $order->getTransport();
        if ($transport->isPickupPlaceType()) {
            return 'OO';
        }

        return 'PO';
    }

    /**
     * @param \App\Model\Order\Order $order
     * @return string
     */
    private function getProductIds(Order $order): string
    {
        $maxProductIdsLength = self::PRODUCT_IDS_MAX_LENGTH + 1;
        $productIds = [];
        foreach ($order->getProductItems() as $productItem) {
            $maxProductIdsLength -= strlen((string)$productItem->getId()) + 1;

            if ($maxProductIdsLength > 0) {
                $productIds[] = $productItem->getId();
            }
        }

        return implode(',', $productIds);
    }

    /**
     * @param \App\Model\Order\Order $order
     * @return string
     */
    private function getProductNames(Order $order): string
    {
        $descriptions = [];
        foreach ($order->getProductItems() as $productItem) {
            $product = $productItem->getProduct();
            if ($product !== null) {
                $descriptions[] = $product->getName($this->domain->getLocale());
            }
        }

        return mb_substr(implode(',', $descriptions), 0, self::PRODUCT_NAMES_MAX_LENGTH, 'UTF-8');
    }

    /**
     * @param string $postcode
     * @return string
     */
    private function getPreparedPostcode(string $postcode): string
    {
        $preparedPostcode = str_replace(' ', '', $postcode);

        return mb_substr($preparedPostcode, 0, 3) . ' ' . mb_substr($preparedPostcode, 3, 2);
    }
}
