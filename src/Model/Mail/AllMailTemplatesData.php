<?php

declare(strict_types=1);

namespace App\Model\Mail;

use Shopsys\FrameworkBundle\Model\Mail\AllMailTemplatesData as BaseAllMailTemplatesData;

class AllMailTemplatesData extends BaseAllMailTemplatesData
{
    public const GIFT_CERTIFICATE = 'gift_certificate';
    public const GIFT_CERTIFICATE_ACTIVATED = 'gift_certificate_activated';

    /**
     * @var \Shopsys\FrameworkBundle\Model\Mail\MailTemplateData|null
     */
    public $giftCertificateTemplate;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Mail\MailTemplateData|null
     */
    public $giftCertificateActivatedTemplate;

    /**
     * @return \Shopsys\FrameworkBundle\Model\Mail\MailTemplateData[]
     */
    public function getAllTemplates()
    {
        $allTemplates = parent::getAllTemplates();
        $allTemplates[] = $this->giftCertificateTemplate;
        $allTemplates[] = $this->giftCertificateActivatedTemplate;

        return $allTemplates;
    }
}
