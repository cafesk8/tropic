<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Security\Authenticator;
use Shopsys\FrameworkBundle\Model\Security\Roles;
use Shopsys\ShopBundle\Form\Front\Login\LoginFormType;
use Shopsys\ShopBundle\Model\Customer\CustomerFacade;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends FrontBaseController
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Security\Authenticator
     */
    private $authenticator;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\CustomerFacade
     */
    private $customerFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Security\Authenticator $authenticator
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(Authenticator $authenticator, CustomerFacade $customerFacade, Domain $domain)
    {
        $this->authenticator = $authenticator;
        $this->customerFacade = $customerFacade;
        $this->domain = $domain;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function loginAction(Request $request)
    {
        if ($this->isGranted(Roles::ROLE_LOGGED_CUSTOMER)) {
            return $this->redirectToRoute('front_homepage');
        }

        $form = $this->getLoginForm();

        try {
            $this->authenticator->checkLoginProcess($request);
        } catch (\Shopsys\FrameworkBundle\Model\Security\Exception\LoginFailedException $e) {
            $form->addError(new FormError(t('This account doesn\'t exist or password is incorrect')));
        }

        return $this->render('@ShopsysShop/Front/Content/Login/loginForm.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param string|null $email
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function windowFormAction(?string $email = null): Response
    {
        return $this->render('@ShopsysShop/Front/Content/Login/windowForm.html.twig', [
            'form' => $this->getLoginForm()->createView(),
            'email' => $email,
        ]);
    }

    /**
     * @param string|null $email
     * @return \Symfony\Component\Form\FormInterface
     */
    private function getLoginForm(?string $email = null): FormInterface
    {
        return $this->createForm(LoginFormType::class, null, [
            'action' => $this->generateUrl('front_login_check'),
            'email' => $email,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function noticeAction(Request $request): Response
    {
        if (!$this->isGranted(Roles::ROLE_LOGGED_CUSTOMER)) {
            $email = $request->get('email');
            $user = $this->customerFacade->findUserByEmailAndDomain($email, $this->domain->getId());

            if ($user !== null) {
                return $this->render('@ShopsysShop/Front/Content/Login/notice.html.twig', [
                    'email' => $email,
                ]);
            }
        }

        return new Response();
    }
}
