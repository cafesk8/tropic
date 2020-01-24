<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\GoPay;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\ShopBundle\Model\Order\Order;

class GoPayFacadeOnCurrentDomain
{
    /**
     * @var \Shopsys\ShopBundle\Model\GoPay\GoPayOrderMapper
     */
    private $goPayOrderMapper;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\ShopBundle\Model\GoPay\GoPayClientFactory
     */
    private $goPayClientFactory;

    /**
     * @param \Shopsys\ShopBundle\Model\GoPay\GoPayClientFactory $goPayClientFactory
     * @param \Shopsys\ShopBundle\Model\GoPay\GoPayOrderMapper $goPayOrderMapper
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        GoPayClientFactory $goPayClientFactory,
        GoPayOrderMapper $goPayOrderMapper,
        Domain $domain
    ) {
        $this->goPayOrderMapper = $goPayOrderMapper;
        $this->domain = $domain;
        $this->goPayClientFactory = $goPayClientFactory;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param string|null $goPayBankSwift
     * @return array
     */
    public function sendPaymentToGoPay(Order $order, ?string $goPayBankSwift): array
    {
        $goPayPaymentData = $this->goPayOrderMapper->createGoPayPaymentData($order, $goPayBankSwift);
        $goPayClient = $this->goPayClientFactory->createByLocale($this->domain->getLocale());
        $response = $goPayClient->sendPaymentToGoPay($goPayPaymentData);

        if ($response->hasSucceed()) {
            return [
                'gatewayUrl' => $response->json['gw_url'],
                'embedJs' => $goPayClient->urlToEmbedJs(),
                'goPayId' => $response->json['id'],
            ];
        }

        throw new \Shopsys\ShopBundle\Model\GoPay\Exception\GoPaySendPaymentException();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\GoPay\GoPayTransaction[] $goPayTransactions
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\GoPay\GoPayResponseData[]
     */
    public function getPaymentStatusesResponseDataByGoPayTransactionAndDomainId(array $goPayTransactions, int $domainId): array
    {
        $responses = [];
        $domainConfig = $this->domain->getDomainConfigById($domainId);
        $goPayClient = $this->goPayClientFactory->createByLocale($domainConfig->getLocale());

        foreach ($goPayTransactions as $goPayTransaction) {
            $responses[] = new GoPayResponseData(
                $goPayClient->getStatus($goPayTransaction->getGoPayId()),
                $goPayTransaction
            );
        }

        return $responses;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return bool
     */
    public function isOrderGoPayUnpaid(Order $order): bool
    {
        return $order->getPayment()->isGoPay() && $order->isGopayPaid() === false;
    }
}
