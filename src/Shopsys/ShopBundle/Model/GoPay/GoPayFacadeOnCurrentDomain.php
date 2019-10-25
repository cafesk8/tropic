<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\GoPay;

use GoPay\Http\Response;
use Psr\Log\LoggerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\ShopBundle\Model\GoPay\Exception\GoPayNotConfiguredException;
use Shopsys\ShopBundle\Model\GoPay\Exception\GoPayPaymentDownloadException;
use Shopsys\ShopBundle\Model\Order\Order;
use Shopsys\ShopBundle\Model\Order\OrderFacade;

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
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\GoPay\GoPayClientFactory $goPayClientFactory
     * @param \Shopsys\ShopBundle\Model\GoPay\GoPayOrderMapper $goPayOrderMapper
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Shopsys\ShopBundle\Model\Order\OrderFacade $orderFacade
     */
    public function __construct(
        GoPayClientFactory $goPayClientFactory,
        GoPayOrderMapper $goPayOrderMapper,
        Domain $domain,
        LoggerInterface $logger,
        OrderFacade $orderFacade
    ) {
        $this->goPayOrderMapper = $goPayOrderMapper;
        $this->domain = $domain;
        $this->goPayClientFactory = $goPayClientFactory;
        $this->logger = $logger;
        $this->orderFacade = $orderFacade;
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
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return \GoPay\Http\Response
     */
    public function getPaymentStatusResponse(Order $order): Response
    {
        $domainConfig = $this->domain->getDomainConfigById($order->getDomainId());
        $goPayClient = $this->goPayClientFactory->createByLocale($domainConfig->getLocale());

        $response = $goPayClient->getStatus($order->getGoPayId());

        return $response;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Order $order
     */
    public function checkOrderGoPayStatus(Order $order): void
    {
        if ($order->isGopayPaid() === true) {
            return;
        }

        try {
            $goPayStatusResponse = $this->getPaymentStatusResponse($order);
            $this->orderFacade->setGoPayStatusAndFik($order, $goPayStatusResponse);
        } catch (GoPayNotConfiguredException $e) {
            $this->logger->addError($e);
            throw $e;
        } catch (GoPayPaymentDownloadException $e) {
            $this->logger->addError($e);
            throw $e;
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Order $order
     * @return bool
     */
    public function isOrderGoPayUnpaid(Order $order): bool
    {
        return $order->getPayment()->isGoPay() && $order->isGopayPaid() === false;
    }
}
