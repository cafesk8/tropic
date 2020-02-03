<?php

declare(strict_types=1);

namespace App\Controller\Front;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade;
use Shopsys\FrameworkBundle\Model\LegalConditions\LegalConditionsFacade;
use Shopsys\FrameworkBundle\Model\Mail\Exception\MailException;
use Shopsys\FrameworkBundle\Model\Security\Authenticator;
use Shopsys\FrameworkBundle\Model\Security\Roles;
use App\Component\Setting\Setting;
use App\Form\Front\Registration\RegistrationFormType;
use App\Model\Article\ArticleFacade;
use App\Model\Customer\CustomerDataFactory;
use App\Model\Customer\CustomerFacade;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RegistrationController extends FrontBaseController
{
    /**
     * @var \App\Model\Customer\CustomerFacade
     */
    private $customerFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Security\Authenticator
     */
    private $authenticator;

    /**
     * @var \Shopsys\FrameworkBundle\Model\LegalConditions\LegalConditionsFacade
     */
    private $legalConditionsFacade;

    /**
     * @var \App\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade
     */
    private $customerMailFacade;

    /**
     * @var \App\Model\Customer\CustomerDataFactory
     */
    private $customerDataFactory;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\FrameworkBundle\Model\Security\Authenticator $authenticator
     * @param \Shopsys\FrameworkBundle\Model\LegalConditions\LegalConditionsFacade $legalConditionsFacade
     * @param \App\Model\Article\ArticleFacade $articleFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade $customerMailFacade
     * @param \App\Model\Customer\CustomerDataFactory $customerDataFactory
     */
    public function __construct(
        Domain $domain,
        CustomerFacade $customerFacade,
        Authenticator $authenticator,
        LegalConditionsFacade $legalConditionsFacade,
        ArticleFacade $articleFacade,
        CustomerMailFacade $customerMailFacade,
        CustomerDataFactory $customerDataFactory
    ) {
        $this->domain = $domain;
        $this->customerFacade = $customerFacade;
        $this->authenticator = $authenticator;
        $this->legalConditionsFacade = $legalConditionsFacade;
        $this->articleFacade = $articleFacade;
        $this->customerMailFacade = $customerMailFacade;
        $this->customerDataFactory = $customerDataFactory;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function existsEmailAction(Request $request)
    {
        $email = $request->get('email');
        $user = $this->customerFacade->findUserByEmailAndDomain($email, $this->domain->getId());

        return new JsonResponse($user !== null);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function registerAction(Request $request)
    {
        if ($this->isGranted(Roles::ROLE_LOGGED_CUSTOMER)) {
            return $this->redirectToRoute('front_homepage');
        }

        $domainId = $this->domain->getId();
        $customerData = $this->customerDataFactory->createForDomainId($domainId);

        $form = $this->createForm(RegistrationFormType::class, $customerData, [
            'domain_id' => $domainId,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customerData = $form->getData();

            /** @var \App\Model\Customer\User $user */
            $user = $this->customerFacade->registerCustomer($customerData);
            try {
                $this->customerMailFacade->sendRegistrationMail($user);
            } catch (\Swift_SwiftException | MailException $exception) {
                $this->getFlashMessageSender()->addErrorFlash(
                    t('Unable to send some e-mails, please contact us for registration verification.')
                );
            }

            $this->authenticator->loginUser($user, $request);

            $this->getFlashMessageSender()->addSuccessFlash(t('You have been successfully registered.'));
            return $this->redirectToRoute('front_customer_edit');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->getFlashMessageSender()->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        return $this->render('Front/Content/Registration/register.html.twig', [
            'form' => $form->createView(),
            'privacyPolicyArticle' => $this->legalConditionsFacade->findPrivacyPolicy($domainId),
            'loyaltyProgramArticle' => $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::LOYALTY_PROGRAM_ARTICLE_ID, $domainId),
        ]);
    }
}
