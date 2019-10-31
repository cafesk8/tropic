<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Security;

use Shopsys\FrameworkBundle\Component\Router\CurrentDomainRouter;
use Shopsys\FrameworkBundle\Model\Order\OrderFlowFacade;
use Shopsys\FrameworkBundle\Model\Security\CustomerLoginHandler as BaseCustomerLoginHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class CustomerLoginHandler extends BaseCustomerLoginHandler
{
    public const LOGGED_FROM_ORDER_SESSION_KEY = 'logged_from_order';

    /**
     * @var \Shopsys\FrameworkBundle\Model\Order\OrderFlowFacade
     */
    private $orderFlowFacade;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    private $session;

    public function __construct(CurrentDomainRouter $router, OrderFlowFacade $orderFlowFacade, SessionInterface $session)
    {
        parent::__construct($router);
        $this->orderFlowFacade = $orderFlowFacade;
        $this->session = $session;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $referer = $request->headers->get('referer');
        if (!$this->isLoginFromOrder($referer)) {
            $this->orderFlowFacade->resetOrderForm();
        } else {
            $this->session->set(self::LOGGED_FROM_ORDER_SESSION_KEY, true);
        }
        return parent::onAuthenticationSuccess($request, $token);
    }
    /**
     * @param string|null $referer
     * @return bool
     */
    private function isLoginFromOrder(?string $referer): bool
    {
        return $referer === $this->router->generate('front_order_index', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
