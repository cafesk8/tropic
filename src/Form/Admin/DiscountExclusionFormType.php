<?php

declare(strict_types=1);

namespace App\Form\Admin;

use Shopsys\FormTypesBundle\MultidomainType;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class DiscountExclusionFormType extends AbstractType
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(Domain $domain)
    {
        $this->domain = $domain;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $optionsByDomainId = [];

        foreach ($this->domain->getAllIds() as $domainId) {
            $optionsByDomainId[$domainId] = ['attr' => ['show_label' => true]];
        }

        $builder
            ->add('registrationDiscountExclusion', MultidomainType::class, [
                'entry_type' => TextareaType::class,
                'label' => t('Informační text k produktu, u kterého není poskytována sleva za registraci'),
                'required' => false,
                'options_by_domain_id' => $optionsByDomainId,
            ])
            ->add('save', SubmitType::class);
    }
}
