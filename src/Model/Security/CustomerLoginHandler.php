<?php

declare(strict_types=1);

namespace App\Model\Security;

use App\Model\Customer\User\CustomerUserFacade;
use App\Model\Order\Preview\OrderPreviewSessionFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Router\CurrentDomainRouter;
use Shopsys\FrameworkBundle\Model\Order\OrderFlowFacade;
use Shopsys\FrameworkBundle\Model\Security\CustomerLoginHandler as BaseCustomerLoginHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class CustomerLoginHandler extends BaseCustomerLoginHandler
{
    public const LOGGED_FROM_ORDER_SESSION_KEY = 'logged_from_order';

    private OrderFlowFacade $orderFlowFacade;

    private SessionInterface $session;

    private CustomerUserFacade $customerUserFacade;

    private Domain $domain;

    private OrderPreviewSessionFacade $orderPreviewSessionFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Router\CurrentDomainRouter $router
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderFlowFacade $orderFlowFacade
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Order\Preview\OrderPreviewSessionFacade $orderPreviewSessionFacade
     */
    public function __construct(
        CurrentDomainRouter $router,
        OrderFlowFacade $orderFlowFacade,
        SessionInterface $session,
        CustomerUserFacade $customerUserFacade,
        Domain $domain,
        OrderPreviewSessionFacade $orderPreviewSessionFacade
    ) {
        parent::__construct($router);
        $this->orderFlowFacade = $orderFlowFacade;
        $this->session = $session;
        $this->customerUserFacade = $customerUserFacade;
        $this->domain = $domain;
        $this->orderPreviewSessionFacade = $orderPreviewSessionFacade;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $this->orderPreviewSessionFacade->unsetOrderPreviewInfoFromSession();
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

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Security\Core\Exception\AuthenticationException $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $response = parent::onAuthenticationFailure($request, $exception);

        if ($response instanceof JsonResponse) {
            $data = ['success' => false];

            if ($this->customerUserFacade->needsToSetNewPassword($request->request->get('front_login_form')['email'], $this->domain->getId())) {
                $data['error_message'] = t(
                    'Přihlašujete se poprvé na novém e-shopu, prosím, nastavte si heslo pomocí funkce „Zapomenuté heslo“',
                    ['%passwordResetUrl%' => $this->router->generate('front_registration_reset_password')]
                );
            } else {
                $data['error_message'] = t('This account doesn\'t exist or password is incorrect');
            }

            return new JsonResponse($data);
        }

        return $response;
    }
}
