<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FrameworkBundle\Form\ColorPickerType;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterValueData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParameterValueFormType extends AbstractType
{
    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('text', TextType::class, ['disabled' => true])
            ->add('rgb', ColorPickerType::class, ['required' => false])
            ->add('hsFeedId', TextType::class, ['required' => false])
            ->add('mallName', TextType::class, ['required' => false]);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ParameterValueData::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
