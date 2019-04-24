<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Model\Order\Order;
use Shopsys\FrameworkBundle\Model\Order\OrderFacade;
use Shopsys\ShopBundle\Model\GoPay\Exception\GoPayNotConfiguredException;
use Shopsys\ShopBundle\Model\GoPay\Exception\GoPayPaymentDownloadException;
use Shopsys\ShopBundle\Model\GoPay\GoPayFacadeOnCurrentDomain;
use Symfony\Component\HttpFoundation\Response;

class GoPayController extends FrontBaseController
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\GoPay\GoPayFacadeOnCurrentDomain
     */
    private $goPayFacadeOnCurrentDomain;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderFacade $orderFacade
     * @param \Shopsys\ShopBundle\Model\GoPay\GoPayFacadeOnCurrentDomain $goPayFacadeOnCurrentDomain
     */
    public function __construct(
        OrderFacade $orderFacade,
        GoPayFacadeOnCurrentDomain $goPayFacadeOnCurrentDomain
    ) {
        $this->orderFacade = $orderFacade;
        $this->goPayFacadeOnCurrentDomain = $goPayFacadeOnCurrentDomain;
    }

    /**
     * @param int $orderId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function goPayStatusNotifyAction(int $orderId): Response
    {
        try {
            $order = $this->orderFacade->getById($orderId);
        } catch (\Shopsys\FrameworkBundle\Model\Order\Exception\OrderNotFoundException $e) {
            return $this->orderNotFoundRedirect();
        }

        if ($order->getGoPayId() > 0) {
            $this->checkOrderGoPayStatus($order);
        } else {
            return $this->orderNotFoundRedirect();
        }

        return new Response();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\Order $order
     */
    private function checkOrderGoPayStatus(Order $order): void
    {
        try {
            $this->goPayFacadeOnCurrentDomain->checkOrderGoPayStatus($order);
        } catch (GoPayNotConfiguredException | GoPayPaymentDownloadException $e) {
            $this->getFlashMessageSender()->addErrorFlash(t('Connection to GoPay gateway failed.'));
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function orderNotFoundRedirect(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $this->getFlashMessageSender()->addErrorFlash(t('Order not found.'));

        return $this->redirectToRoute('front_cart');
    }
}
