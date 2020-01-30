<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Front\Cart;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class AddProductFormType extends AbstractType
{
    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('productId', HiddenType::class, [
                'constraints' => [
                    new Constraints\GreaterThan(0),
                    new Constraints\Regex(['pattern' => '/^\d+$/']),
                ],
            ])
            ->add('quantity', NumberType::class, [
                'constraints' => [
                    new Constraints\GreaterThanOrEqual($options['minimum_amount']),
                    new Constraints\Regex(['pattern' => '/^\d+$/']),
                ],
            ])
            ->add('add', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'attr' => ['novalidate' => 'novalidate'],
            'csrf_protection' => false, // CSRF is not necessary (and can be annoying) in this form
        ])
            ->setRequired('minimum_amount')
            ->setAllowedTypes('minimum_amount', 'int');
    }
}
