<?php

declare(strict_types=1);

namespace App\Form\Admin;

use Shopsys\FormTypesBundle\MultidomainType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class DiscountExclusionFormType extends AbstractType
{
    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('registrationDiscountExclusion', MultidomainType::class, [
                'entry_type' => TextareaType::class,
                'label' => t('Informační text k produktu, u kterého není poskytována sleva za registraci'),
                'required' => false,
            ])
            ->add('save', SubmitType::class);
    }
}
