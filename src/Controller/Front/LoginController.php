<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Form\Front\Login\LoginFormType;
use App\Model\Customer\User\CustomerUserFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Security\Authenticator;
use Shopsys\FrameworkBundle\Model\Security\Roles;
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
     * @var \App\Model\Customer\User\CustomerUserFacade
     */
    private $customerUserFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Security\Authenticator $authenticator
     * @param \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(Authenticator $authenticator, CustomerUserFacade $customerUserFacade, Domain $domain)
    {
        $this->authenticator = $authenticator;
        $this->customerUserFacade = $customerUserFacade;
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

        return $this->render('Front/Content/Login/loginForm.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param string|null $email
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function windowFormAction(?string $email = null): Response
    {
        return $this->render('Front/Content/Login/windowForm.html.twig', [
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
            $customerUser = $this->customerUserFacade->findCustomerUserByEmailAndDomain($email, $this->domain->getId());

            if ($customerUser !== null) {
                return $this->render('Front/Content/Order/noticeRegistered.html.twig', [
                    'email' => $email,
                ]);
            } else {
                return $this->render('Front/Content/Order/noticeUnregistered.html.twig', []);
            }
        }

        return new Response();
    }
}
