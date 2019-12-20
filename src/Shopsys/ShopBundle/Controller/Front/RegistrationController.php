<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade;
use Shopsys\FrameworkBundle\Model\LegalConditions\LegalConditionsFacade;
use Shopsys\FrameworkBundle\Model\Mail\Exception\MailException;
use Shopsys\FrameworkBundle\Model\Security\Authenticator;
use Shopsys\ShopBundle\Component\CardEan\CardEanFacade;
use Shopsys\ShopBundle\Component\Setting\Setting;
use Shopsys\ShopBundle\Form\Front\Registration\RegistrationFormType;
use Shopsys\ShopBundle\Model\Article\ArticleFacade;
use Shopsys\ShopBundle\Model\Customer\CustomerDataFactory;
use Shopsys\ShopBundle\Model\Customer\CustomerFacade;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RegistrationController extends FrontBaseController
{
    /**
     * @var \Shopsys\ShopBundle\Model\Customer\CustomerFacade
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
     * @var \Shopsys\ShopBundle\Model\Article\ArticleFacade
     */
    private $articleFacade;

    /**
     * @var \Shopsys\ShopBundle\Component\CardEan\CardEanFacade
     */
    private $cardEanFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade
     */
    private $customerMailFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\CustomerDataFactory
     */
    private $customerDataFactory;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\FrameworkBundle\Model\Security\Authenticator $authenticator
     * @param \Shopsys\FrameworkBundle\Model\LegalConditions\LegalConditionsFacade $legalConditionsFacade
     * @param \Shopsys\ShopBundle\Component\CardEan\CardEanFacade $cardEanFacade
     * @param \Shopsys\ShopBundle\Model\Article\ArticleFacade $articleFacade
     * @param \Shopsys\FrameworkBundle\Model\Customer\Mail\CustomerMailFacade $customerMailFacade
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerDataFactory $customerDataFactory
     */
    public function __construct(
        Domain $domain,
        CustomerFacade $customerFacade,
        Authenticator $authenticator,
        LegalConditionsFacade $legalConditionsFacade,
        CardEanFacade $cardEanFacade,
        ArticleFacade $articleFacade,
        CustomerMailFacade $customerMailFacade,
        CustomerDataFactory $customerDataFactory
    ) {
        $this->domain = $domain;
        $this->customerFacade = $customerFacade;
        $this->authenticator = $authenticator;
        $this->legalConditionsFacade = $legalConditionsFacade;
        $this->cardEanFacade = $cardEanFacade;
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
        $domainId = $this->domain->getId();
        $customerData = $this->customerDataFactory->createForDomainId($domainId);

        $form = $this->createForm(RegistrationFormType::class, $customerData, [
            'domain_id' => $domainId,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $customerData = $form->getData();

            /** @var \Shopsys\ShopBundle\Model\Customer\User $user */
            $user = $this->customerFacade->registerCustomer($customerData);
            try {
                $this->customerMailFacade->sendRegistrationMail($user);
            } catch (\Swift_SwiftException | MailException $exception) {
                $this->getFlashMessageSender()->addErrorFlash(
                    t('Unable to send some e-mails, please contact us for registration verification.')
                );
            }

            $this->cardEanFacade->addPrereneratedEanToUserAndFlush($user);

            $this->authenticator->loginUser($user, $request);

            $this->getFlashMessageSender()->addSuccessFlash(t('You have been successfully registered.'));
            return $this->redirectToRoute('front_customer_edit');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->getFlashMessageSender()->addErrorFlash(t('Please check the correctness of all data filled.'));
        }

        return $this->render('@ShopsysShop/Front/Content/Registration/register.html.twig', [
            'form' => $form->createView(),
            'privacyPolicyArticle' => $this->legalConditionsFacade->findPrivacyPolicy($domainId),
            'bushmanClubArticle' => $this->articleFacade->findArticleBySettingValueAndDomainId(Setting::BUSHMAN_CLUB_ARTICLE_ID, $domainId),
        ]);
    }
}
