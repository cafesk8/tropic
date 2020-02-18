<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Component\Setting\Setting;
use App\Form\Front\Registration\RegistrationFormType;
use App\Model\Article\ArticleFacade;
use App\Model\Customer\User\CustomerUserFacade;
use App\Model\Customer\User\CustomerUserUpdateDataFactory;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade;
use Shopsys\FrameworkBundle\Model\LegalConditions\LegalConditionsFacade;
use Shopsys\FrameworkBundle\Model\Mail\Exception\MailException;
use Shopsys\FrameworkBundle\Model\Security\Authenticator;
use Shopsys\FrameworkBundle\Model\Security\Roles;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RegistrationController extends FrontBaseController
{
    /**
     * @var \App\Model\Customer\User\CustomerUserFacade
     */
    private $customerUserFacade;

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
     * @var \App\Model\Customer\User\CustomerUserUpdateDataFactory
     */
    private $customerUserUpdateDataFactory;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
     * @param \Shopsys\FrameworkBundle\Model\Security\Authenticator $authenticator
     * @param \Shopsys\FrameworkBundle\Model\LegalConditions\LegalConditionsFacade $legalConditionsFacade
     * @param \App\Model\Article\ArticleFacade $articleFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade $customerMailFacade
     * @param \App\Model\Customer\User\CustomerUserUpdateDataFactory $customerUserUpdateDataFactory
     */
    public function __construct(
        Domain $domain,
        CustomerUserFacade $customerUserFacade,
        Authenticator $authenticator,
        LegalConditionsFacade $legalConditionsFacade,
        ArticleFacade $articleFacade,
        CustomerMailFacade $customerMailFacade,
        CustomerUserUpdateDataFactory $customerUserUpdateDataFactory
    ) {
        $this->domain = $domain;
        $this->customerUserFacade = $customerUserFacade;
        $this->authenticator = $authenticator;
        $this->legalConditionsFacade = $legalConditionsFacade;
        $this->articleFacade = $articleFacade;
        $this->customerMailFacade = $customerMailFacade;
        $this->customerUserUpdateDataFactory = $customerUserUpdateDataFactory;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function existsEmailAction(Request $request)
    {
        $email = $request->get('email');
        $customerUser = $this->customerUserFacade->findCustomerUserByEmailAndDomain($email, $this->domain->getId());

        return new JsonResponse($customerUser !== null);
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
        $customerUserUpdateData = $this->customerUserUpdateDataFactory->createForDomainId($domainId);

        $form = $this->createForm(RegistrationFormType::class, $customerUserUpdateData, [
            'domain_id' => $domainId,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customerUserUpdateData = $form->getData();

            /** @var \App\Model\Customer\User\CustomerUser $customerUser */
            $customerUser = $this->customerUserFacade->registerCustomer($customerUserUpdateData);
            try {
                $this->customerMailFacade->sendRegistrationMail($customerUser);
            } catch (\Swift_SwiftException | MailException $exception) {
                $this->getFlashMessageSender()->addErrorFlash(
                    t('Unable to send some e-mails, please contact us for registration verification.')
                );
            }

            $this->authenticator->loginUser($customerUser, $request);

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
