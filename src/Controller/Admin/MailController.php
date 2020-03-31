<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Mail\AllMailTemplatesData;
use App\Model\Order\GiftCertificate\Mail\OrderGiftCertificateMail;
use App\Model\Order\Mail\OrderMail;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Controller\Admin\MailController as BaseMailController;
use Shopsys\FrameworkBundle\Model\Customer\Mail\RegistrationMail;
use Shopsys\FrameworkBundle\Model\Customer\Mail\ResetPasswordMail;
use Shopsys\FrameworkBundle\Model\Mail\MailTemplateFacade;
use Shopsys\FrameworkBundle\Model\Mail\Setting\MailSettingFacade;
use Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusFacade;
use Shopsys\FrameworkBundle\Model\PersonalData\Mail\PersonalDataAccessMail;
use Shopsys\FrameworkBundle\Model\PersonalData\Mail\PersonalDataExportMail;

/**
 * @property \App\Model\Order\Mail\OrderMail $orderMail
 * @property \App\Model\Mail\MailTemplateFacade $mailTemplateFacade
 */
class MailController extends BaseMailController
{
    /**
     * @var \App\Model\Order\GiftCertificate\Mail\OrderGiftCertificateMail
     */
    private $orderGiftCertificateMail;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Customer\Mail\ResetPasswordMail $resetPasswordMail
     * @param \App\Model\Order\Mail\OrderMail $orderMail
     * @param \Shopsys\FrameworkBundle\Model\Customer\Mail\RegistrationMail $registrationMail
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \App\Model\Mail\MailTemplateFacade $mailTemplateFacade
     * @param \Shopsys\FrameworkBundle\Model\Mail\Setting\MailSettingFacade $mailSettingFacade
     * @param \Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusFacade $orderStatusFacade
     * @param \Shopsys\FrameworkBundle\Model\PersonalData\Mail\PersonalDataAccessMail $personalDataAccessMail
     * @param \Shopsys\FrameworkBundle\Model\PersonalData\Mail\PersonalDataExportMail $personalDataExportMail
     * @param \App\Model\Order\GiftCertificate\Mail\OrderGiftCertificateMail $orderGiftCertificateMail
     */
    public function __construct(
        ResetPasswordMail $resetPasswordMail,
        \App\Model\Order\Mail\OrderMail $orderMail,
        RegistrationMail $registrationMail,
        AdminDomainTabsFacade $adminDomainTabsFacade,
        MailTemplateFacade $mailTemplateFacade,
        MailSettingFacade $mailSettingFacade,
        OrderStatusFacade $orderStatusFacade,
        PersonalDataAccessMail $personalDataAccessMail,
        PersonalDataExportMail $personalDataExportMail,
        OrderGiftCertificateMail $orderGiftCertificateMail
    ) {
        parent::__construct(
            $resetPasswordMail,
            $orderMail,
            $registrationMail,
            $adminDomainTabsFacade,
            $mailTemplateFacade,
            $mailSettingFacade,
            $orderStatusFacade,
            $personalDataAccessMail,
            $personalDataExportMail
        );
        $this->orderGiftCertificateMail = $orderGiftCertificateMail;
    }

    /**
     * @return array
     */
    protected function getTemplateParameters()
    {
        $selectedDomainId = $this->adminDomainTabsFacade->getSelectedDomainId();
        $templateParameters = parent::getTemplateParameters();
        $templateParameters['giftCertificateTemplate'] = $this->mailTemplateFacade->get(
            AllMailTemplatesData::GIFT_CERTIFICATE,
            $selectedDomainId
        );
        $templateParameters['giftCertificateVariables'] = $this->orderGiftCertificateMail->getTemplateVariables();
        $templateParameters['giftCertificateVariablesLabels'] = $this->getGiftCertificateVariablesLabels();
        $templateParameters['giftCertificateActivatedTemplate'] = $this->mailTemplateFacade->get(
            AllMailTemplatesData::GIFT_CERTIFICATE_ACTIVATED,
            $selectedDomainId
        );
        $templateParameters['giftCertificateActivatedVariables'] = $this->orderGiftCertificateMail->getTemplateVariables();
        $templateParameters['giftCertificateActivatedVariablesLabels'] = $this->getGiftCertificateVariablesLabels();

        return $templateParameters;
    }

    /**
     * @return array
     */
    protected function getOrderStatusVariablesLabels(): array
    {
        $orderStatusVariablesLables = parent::getOrderStatusVariablesLabels();

        $orderStatusVariablesLables[OrderMail::VARIABLE_PREPARED_PRODUCTS] =
            t('Seznam již dostupného zboží v objednávce (název, dostupné množství, cena za jednotku s DPH, celková cena za položku s DPH)');
        $orderStatusVariablesLables[OrderMail::VARIABLE_TRACKING_URL] = t('Odkaz pro sledování zásilky');
        $orderStatusVariablesLables[OrderMail::VARIABLE_TRACKING_NUMBER] = t('Číslo pro sledování zásilky');

        return $orderStatusVariablesLables;
    }

    /**
     * @return array
     */
    protected function getGiftCertificateVariablesLabels(): array
    {
        return [
            OrderGiftCertificateMail::VARIABLE_GIFT_CERTIFICATE_CODE => t('Kód dárkového poukazu'),
            OrderGiftCertificateMail::VARIABLE_GIFT_CERTIFICATE_CURRENCY => t('Měna dárkového poukazu'),
            OrderGiftCertificateMail::VARIABLE_GIFT_CERTIFICATE_VALID_UNTIL => t('Čas konce platnosti poukazu'),
            OrderGiftCertificateMail::VARIABLE_GIFT_CERTIFICATE_VALUE => t('Hodnota dárkového poukazu'),
            OrderGiftCertificateMail::VARIABLE_ORDER_NUMBER => t('Číslo objednávky, ve které byl dárkový poukaz zakoupen'),
        ];
    }
}
