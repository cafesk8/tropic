<?php declare(strict_types=1);

namespace App\Form\Admin;

use Shopsys\FrameworkBundle\Form\GroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HeaderTextSettingFormType extends AbstractType
{
    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builderPublishedDataGroup = $builder->create('textInHeader', GroupType::class, [
            'label' => t('Text v hlavičce'),
        ]);

        $builderPublishedDataGroup
            ->add('headerTitle', TextType::class, [
                'required' => false,
                'label' => t('Nadpis'),
            ])
            ->add('headerText', TextType::class, [
                'required' => false,
                'label' => t('Popis'),
            ])
            ->add('headerLink', TextType::class, [
                'required' => false,
                'label' => t('Odkaz'),
            ]);

        $builder
            ->add($builderPublishedDataGroup)
            ->add('save', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
