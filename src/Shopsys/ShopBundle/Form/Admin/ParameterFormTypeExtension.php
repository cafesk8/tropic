<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FrameworkBundle\Form\Admin\Product\Parameter\ParameterFormType;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterData;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParameterFormTypeExtension extends AbstractTypeExtension
{
    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('visibleOnFrontend', CheckboxType::class);
        $builder->add('mallId', TextType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ParameterData::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }

    /**
     * @return string The name of the type being extended
     */
    public function getExtendedType(): string
    {
        return ParameterFormType::class;
    }
}
