<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Mail\AllMailTemplatesData;
use App\Model\Order\Mail\OrderMail;
use Shopsys\FrameworkBundle\Controller\Admin\MailController as BaseMailController;

/**
 * @property \App\Model\Order\Mail\OrderMail $orderMail
 * @method __construct(\Shopsys\FrameworkBundle\Model\Customer\Mail\ResetPasswordMail $resetPasswordMail, \App\Model\Order\Mail\OrderMail $orderMail, \Shopsys\FrameworkBundle\Model\Customer\Mail\RegistrationMail $registrationMail, \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade, \App\Model\Mail\MailTemplateFacade $mailTemplateFacade, \Shopsys\FrameworkBundle\Model\Mail\Setting\MailSettingFacade $mailSettingFacade, \Shopsys\FrameworkBundle\Model\Order\Status\OrderStatusFacade $orderStatusFacade, \Shopsys\FrameworkBundle\Model\PersonalData\Mail\PersonalDataAccessMail $personalDataAccessMail, \Shopsys\FrameworkBundle\Model\PersonalData\Mail\PersonalDataExportMail $personalDataExportMail)
 * @property \App\Model\Mail\MailTemplateFacade $mailTemplateFacade
 */
class MailController extends BaseMailController
{
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
        $templateParameters['giftCertificateActivatedTemplate'] = $this->mailTemplateFacade->get(
            AllMailTemplatesData::GIFT_CERTIFICATE_ACTIVATED,
            $selectedDomainId
        );

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
}
