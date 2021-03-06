<?php

declare(strict_types=1);

namespace App\Form\Front\Cart;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
            ->add('quantity', TextType::class, [
                'data' => 1,
                'constraints' => [
                    new Constraints\Regex(['pattern' => '/^\d+$/']),
                    new Constraints\Callback([
                        'callback' => [$this, 'validateMinimumAmount'],
                        'payload' => [
                            '%amount%' => $options['minimum_amount'],
                            '%unitName%' => $options['unit_name'],
                        ],
                    ]),
                ],
            ])
            ->add('onlyRefresh', HiddenType::class, [
                'data' => $options['only_refresh'],
                'mapped' => false,
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
            'only_refresh' => false,
        ])
            ->setRequired(['minimum_amount', 'only_refresh', 'unit_name'])
            ->setAllowedTypes('minimum_amount', 'int')
            ->setAllowedTypes('only_refresh', 'bool');
    }

    /**
     * @param int $quantity
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     * @param array $data
     */
    public function validateMinimumAmount(int $quantity, ExecutionContextInterface $context, array $data): void
    {
        if ($quantity < $data['%amount%']) {
            $context->addViolation('Tento produkt lze nakoupit v minim??ln??m mno??stv?? %amount%&nbsp;%unitName%', $data);
        }
    }
}
