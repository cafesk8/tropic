<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\ShopBundle\Model\Order\Order;
use Shopsys\ShopBundle\Model\Order\OrderFacade;
use Shopsys\ShopBundle\Model\PayPal\PayPalFacade;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class PayPalController extends FrontBaseController
{
    /**
     * @var \Shopsys\ShopBundle\Model\PayPal\PayPalFacade
     */
    private $payPalFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\PayPal\PayPalFacade $payPalFacade
     * @param \Shopsys\ShopBundle\Model\Order\OrderFacade $orderFacade
     */
    public function __construct(PayPalFacade $payPalFacade, OrderFacade $orderFacade)
    {
        $this->payPalFacade = $payPalFacade;
        $this->orderFacade = $orderFacade;
    }

    /**
     * @param int $orderId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function payPalStatusNotifyAction(int $orderId): Response
    {
        try {
            $order = $this->orderFacade->getById($orderId);
        } catch (\Shopsys\FrameworkBundle\Model\Order\Exception\OrderNotFoundException $e) {
            return $this->orderNotFoundRedirect();
        }

        /** @var \Shopsys\ShopBundle\Model\Order\Order $order */
        if ($order->getPayPalId() !== null) {
            $this->checkOrderGoPayStatus($order);
        } else {
            return $this->orderNotFoundRedirect();
        }

        return new Response();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     */
    private function checkOrderGoPayStatus(Order $order): void
    {
        try {
            $this->payPalFacade->updateOrderPayPalStatus($order);
        } catch (\PayPal\Exception\PayPalConnectionException $e) {
            $this->getFlashMessageSender()->addErrorFlash(t('Connection to PayPal gateway failed.'));
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function orderNotFoundRedirect(): RedirectResponse
    {
        $this->getFlashMessageSender()->addErrorFlash(t('Order not found.'));

        return $this->redirectToRoute('front_cart');
    }
}
