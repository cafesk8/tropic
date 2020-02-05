<?php

declare(strict_types=1);

namespace App\Controller\Front;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\ContactForm\ContactFormSettingsFacade;
use Shopsys\FrameworkBundle\Model\LegalConditions\LegalConditionsFacade;
use App\Form\Front\Contact\ContactFormType;
use App\Model\ContactForm\ContactFormData;
use App\Model\ContactForm\ContactFormFacade;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ContactFormController extends FrontBaseController
{
    /**
     * @var \App\Model\ContactForm\ContactFormFacade
     */
    private $contactFormFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\LegalConditions\LegalConditionsFacade
     */
    private $legalConditionsFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\ContactForm\ContactFormSettingsFacade
     */
    private $contactFormSettingsFacade;

    /**
     * @param \App\Model\ContactForm\ContactFormFacade $contactFormFacade
     * @param \Shopsys\FrameworkBundle\Model\LegalConditions\LegalConditionsFacade $legalConditionsFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\ContactForm\ContactFormSettingsFacade $contactFormSettingsFacade
     */
    public function __construct(
        ContactFormFacade $contactFormFacade,
        LegalConditionsFacade $legalConditionsFacade,
        Domain $domain,
        ContactFormSettingsFacade $contactFormSettingsFacade
    ) {
        $this->contactFormFacade = $contactFormFacade;
        $this->legalConditionsFacade = $legalConditionsFacade;
        $this->domain = $domain;
        $this->contactFormSettingsFacade = $contactFormSettingsFacade;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function sendAction(Request $request)
    {
        $privacyPolicyArticle = $this->legalConditionsFacade->findPrivacyPolicy($this->domain->getId());

        $form = $this->createForm(ContactFormType::class, new ContactFormData(), [
            'action' => $this->generateUrl('front_contact_form_send'),
        ]);
        $form->handleRequest($request);

        $message = '';
        if ($form->isSubmitted() && $form->isValid()) {
            $contactFormData = $form->getData();

            try {
                $this->contactFormFacade->sendMail($contactFormData);
                $form = $this->createForm(ContactFormType::class, new ContactFormData(), [
                    'action' => $this->generateUrl('front_contact_form_send'),
                ]);
                $message = t('Thank you, your message has been sent.');
            } catch (\Shopsys\FrameworkBundle\Model\Mail\Exception\MailException $ex) {
                $message = t('Error occurred when sending e-mail.');
            }
        }

        $contactFormHtml = $this->renderView('Front/Content/ContactForm/contactForm.html.twig', [
            'form' => $form->createView(),
            'privacyPolicyArticle' => $privacyPolicyArticle,
        ]);

        return new JsonResponse([
            'contactFormHtml' => $contactFormHtml,
            'message' => $message,
        ]);
    }

    public function indexAction()
    {
        $privacyPolicyArticle = $this->legalConditionsFacade->findPrivacyPolicy($this->domain->getId());

        $form = $this->createForm(ContactFormType::class, new ContactFormData(), [
            'action' => $this->generateUrl('front_contact_form_send'),
        ]);

        return $this->render('Front/Content/ContactForm/contactForm.html.twig', [
            'form' => $form->createView(),
            'privacyPolicyArticle' => $privacyPolicyArticle,
            'mainText' => $this->contactFormSettingsFacade->getMainText($this->domain->getId())
        ]);
    }
}
