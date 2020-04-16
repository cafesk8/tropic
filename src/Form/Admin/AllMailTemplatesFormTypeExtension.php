<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\Mail\MailTemplateFacade;
use Shopsys\FrameworkBundle\Form\Admin\Mail\AllMailTemplatesFormType;
use Shopsys\FrameworkBundle\Form\Admin\Mail\MailTemplateFormType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class AllMailTemplatesFormTypeExtension extends AbstractTypeExtension
{
    /**
     * @var \App\Model\Mail\MailTemplateFacade
     */
    private $mailTemplateFacade;

    /**
     * @param \App\Model\Mail\MailTemplateFacade $mailTemplateFacade
     */
    public function __construct(MailTemplateFacade $mailTemplateFacade)
    {
        $this->mailTemplateFacade = $mailTemplateFacade;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \App\Model\Mail\AllMailTemplatesData $data */
        $data = $options['data'];
        $domainId = $data->domainId;

        $builder
            ->add('giftCertificateTemplate', MailTemplateFormType::class, [
                'entity' => $this->mailTemplateFacade->get($data->giftCertificateTemplate->name, $domainId),
            ])
            ->add('giftCertificateActivatedTemplate', MailTemplateFormType::class, [
                'entity' => $this->mailTemplateFacade->get($data->giftCertificateActivatedTemplate->name, $domainId),
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        yield AllMailTemplatesFormType::class;
    }
}
