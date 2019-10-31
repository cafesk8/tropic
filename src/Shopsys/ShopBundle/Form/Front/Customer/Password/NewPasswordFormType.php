<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Front\Customer\Password;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class NewPasswordFormType extends AbstractType
{
    public const OPTION_REPEATED = 'repeated';

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options[self::OPTION_REPEATED] === true) {
            $builder
                ->add('newPassword', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'options' => [
                        'attr' => ['autocomplete' => 'new-password'],
                    ],
                    'first_options' => [
                        'constraints' => [
                            new Constraints\NotBlank(['message' => 'Please enter password']),
                            new Constraints\Length(['min' => 6, 'minMessage' => 'Password cannot be longer then {{ limit }} characters']),
                        ],
                    ],
                    'invalid_message' => 'Passwords do not match',
                ]);
        } else {
            $builder
                ->add('newPassword', PasswordType::class, [
                    'attr' => ['autocomplete' => 'new-password'],
                    'constraints' => [
                        new Constraints\NotBlank(['message' => 'Please enter password']),
                        new Constraints\Length(['min' => 6, 'minMessage' => 'Password cannot be longer then {{ limit }} characters']),
                    ],
                ]);
        }

        $builder->add('submit', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(self::OPTION_REPEATED)
            ->addAllowedTypes(self::OPTION_REPEATED, 'bool')
            ->setDefaults([
                'attr' => ['novalidate' => 'novalidate'],
            ]);
    }
}
