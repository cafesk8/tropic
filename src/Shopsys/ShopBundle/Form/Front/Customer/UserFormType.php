<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Front\Customer;

use Shopsys\FrameworkBundle\Form\Constraints\FieldsAreNotIdentical;
use Shopsys\FrameworkBundle\Form\Constraints\NotIdenticalToEmailLocalPart;
use Shopsys\FrameworkBundle\Model\Customer\User;
use Shopsys\FrameworkBundle\Model\Customer\UserData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class UserFormType extends AbstractType
{
    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank(['message' => 'Please enter first name']),
                    new Constraints\Length(['max' => 60, 'maxMessage' => 'First name cannot be longer than {{ limit }} characters']),
                ],
            ])
            ->add('lastName', TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank(['message' => 'Please enter last name']),
                    new Constraints\Length(['max' => 30, 'maxMessage' => 'Last name cannot be longer than {{ limit }} characters']),
                ],
            ])
            ->add('email', EmailType::class, [
                'attr' => ['readonly' => true],
                'required' => true,
            ])
            ->add('telephone', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Constraints\Length([
                        'max' => 20,
                        'maxMessage' => 'Telephone number cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => false,
                'options' => [
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'first_options' => [
                    'constraints' => [
                        new Constraints\Length(['min' => 6, 'minMessage' => 'Password cannot be longer than {{ limit }} characters']),
                    ],
                ],
                'invalid_message' => 'Passwords do not match',
            ]);

        /** @var \Shopsys\ShopBundle\Model\Customer\User $customer */
        $customer = $options['user'];

        if ($customer->isMemberOfBushmanClub() === false) {
            $builder
                ->add('memberOfBushmanClub', CheckboxType::class, [
                    'required' => true,
                ]);
        }
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('user')
            ->addAllowedTypes('user', User::class)
            ->setDefaults([
                'data_class' => UserData::class,
                'attr' => ['novalidate' => 'novalidate'],
                'constraints' => [
                    new FieldsAreNotIdentical([
                        'field1' => 'email',
                        'field2' => 'password',
                        'errorPath' => 'password',
                        'message' => 'Password cannot be same as e-mail',
                    ]),
                    new NotIdenticalToEmailLocalPart([
                        'password' => 'password',
                        'email' => 'email',
                        'errorPath' => 'password',
                        'message' => 'Password cannot be same as part of e-mail before at sign',
                    ]),
                ],
        ]);
    }
}
