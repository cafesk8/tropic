<?php

declare(strict_types=1);

namespace App\Model\Mail;

use App\Model\Order\Status\OrderStatusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileFacade;
use Shopsys\FrameworkBundle\Model\Mail\MailTemplateAttachmentFilepathProvider;
use Shopsys\FrameworkBundle\Model\Mail\MailTemplateDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Mail\MailTemplateFacade as BaseMailTemplateFacade;
use Shopsys\FrameworkBundle\Model\Mail\MailTemplateFactoryInterface;
use Shopsys\FrameworkBundle\Model\Mail\MailTemplateRepository;

/**
 * @method \Shopsys\FrameworkBundle\Model\Mail\MailTemplate[] getFilteredOrderStatusMailTemplatesIndexedByOrderStatusId(\App\Model\Order\Status\OrderStatus[] $orderStatuses, \Shopsys\FrameworkBundle\Model\Mail\MailTemplate[] $mailTemplates)
 */
class MailTemplateFacade extends BaseMailTemplateFacade
{
    /**
     * @var \App\Model\Mail\AllMailTemplatesDataFactory
     */
    private $allMailTemplatesDataFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Mail\MailTemplateRepository $mailTemplateRepository
     * @param \App\Model\Order\Status\OrderStatusRepository $orderStatusRepository
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileFacade $uploadedFileFacade
     * @param \Shopsys\FrameworkBundle\Model\Mail\MailTemplateFactoryInterface $mailTemplateFactory
     * @param \Shopsys\FrameworkBundle\Model\Mail\MailTemplateDataFactoryInterface $mailTemplateDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Mail\MailTemplateAttachmentFilepathProvider $mailTemplateAttachmentFilepathProvider
     * @param \App\Model\Mail\AllMailTemplatesDataFactory $allMailTemplatesDataFactory
     */
    public function __construct(
        EntityManagerInterface $em,
        MailTemplateRepository $mailTemplateRepository,
        OrderStatusRepository $orderStatusRepository,
        Domain $domain,
        UploadedFileFacade $uploadedFileFacade,
        MailTemplateFactoryInterface $mailTemplateFactory,
        MailTemplateDataFactoryInterface $mailTemplateDataFactory,
        MailTemplateAttachmentFilepathProvider $mailTemplateAttachmentFilepathProvider,
        AllMailTemplatesDataFactory $allMailTemplatesDataFactory
    ) {
        parent::__construct($em, $mailTemplateRepository, $orderStatusRepository, $domain, $uploadedFileFacade, $mailTemplateFactory, $mailTemplateDataFactory, $mailTemplateAttachmentFilepathProvider);
        $this->allMailTemplatesDataFactory = $allMailTemplatesDataFactory;
    }

    /**
     * @param int $domainId
     * @return \App\Model\Mail\AllMailTemplatesData
     */
    public function getAllMailTemplatesDataByDomainId($domainId)
    {
        $allMailTemplatesData = $this->allMailTemplatesDataFactory->createFromBase(parent::getAllMailTemplatesDataByDomainId($domainId));

        $giftCertificateTemplate = $this->mailTemplateRepository
            ->findByNameAndDomainId(AllMailTemplatesData::GIFT_CERTIFICATE, $domainId);

        if ($giftCertificateTemplate !== null) {
            $giftCertificateTemplateData = $this->mailTemplateDataFactory->createFromMailTemplate($giftCertificateTemplate);
        } else {
            $giftCertificateTemplateData = $this->mailTemplateDataFactory->create();
        }

        $allMailTemplatesData->giftCertificateTemplate = $giftCertificateTemplateData;

        $giftCertificateActivatedTemplate = $this->mailTemplateRepository
            ->findByNameAndDomainId(AllMailTemplatesData::GIFT_CERTIFICATE_ACTIVATED, $domainId);

        if ($giftCertificateActivatedTemplate !== null) {
            $giftCertificateActivatedTemplateData = $this->mailTemplateDataFactory->createFromMailTemplate($giftCertificateActivatedTemplate);
        } else {
            $giftCertificateActivatedTemplateData = $this->mailTemplateDataFactory->create();
        }

        $allMailTemplatesData->giftCertificateActivatedTemplate = $giftCertificateActivatedTemplateData;

        return $allMailTemplatesData;
    }
}
