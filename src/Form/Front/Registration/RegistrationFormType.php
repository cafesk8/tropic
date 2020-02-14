<?php

declare(strict_types=1);

namespace App\Form\Front\Registration;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Form\TimedFormTypeExtension;
use Shopsys\FrameworkBundle\Form\Constraints\Email;
use Shopsys\FrameworkBundle\Form\Constraints\FieldsAreNotIdentical;
use Shopsys\FrameworkBundle\Form\Constraints\NotIdenticalToEmailLocalPart;
use Shopsys\FrameworkBundle\Form\Constraints\UniqueEmail;
use Shopsys\FrameworkBundle\Form\HoneyPotType;
use Shopsys\FrameworkBundle\Form\ValidationGroup;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressData;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserData;
use App\Component\Domain\DomainHelper;
use App\Model\Country\CountryFacade;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class RegistrationFormType extends AbstractType
{
    public const VALIDATION_GROUP_LOYALTY_PROGRAM_MEMBER = 'loyaltyProgramMember';

    /**
     * @var \App\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \App\Model\Country\CountryFacade $countryFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(CountryFacade $countryFacade, Domain $domain)
    {
        $this->countryFacade = $countryFacade;
        $this->domain = $domain;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $customerUserData = $this->getCustomerUserDataBuilder($builder);
        $deliveryAddressData = $this->getDeliveryAddressDataBuilder($builder, $options);

        $builder
            ->add($customerUserData)
            ->add($deliveryAddressData)
            ->add('privacyPolicy', CheckboxType::class, [
                'required' => true,
                'mapped' => false,
                'constraints' => [
                    new Constraints\NotBlank(['message' => 'You have to agree with privacy policy']),
                ],
            ])
            ->add('save', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $defaults = [
            'data_class' => CustomerUserUpdateData::class,
            'attr' => ['novalidate' => 'novalidate'],
            TimedFormTypeExtension::OPTION_ENABLED => true,
            'constraints' => [
                new FieldsAreNotIdentical([
                    'field1' => 'customerUserData.email',
                    'field2' => 'customerUserData.password',
                    'errorPath' => 'customerUserData.password',
                    'message' => 'Password cannot be same as e-mail',
                ]),
                new NotIdenticalToEmailLocalPart([
                    'password' => 'customerUserData.password',
                    'email' => 'customerUserData.email',
                    'errorPath' => 'customerUserData.password',
                    'message' => 'Password cannot be same as part of e-mail before at sign',
                ]),
            ],
            'validation_groups' => function (FormInterface $form) {
                $validationGroups = [ValidationGroup::VALIDATION_GROUP_DEFAULT];
                /** @var \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData $customerUserUpdateData */
                $customerUserUpdateData = $form->getData();
                /** @var \App\Model\Customer\User\CustomerUserData $customerUserData */
                $customerUserData = $customerUserUpdateData->customerUserData;

                if ($customerUserData->memberOfBushmanClub) {
                    $validationGroups[] = self::VALIDATION_GROUP_LOYALTY_PROGRAM_MEMBER;
                }

                return $validationGroups;
            },
        ];

        $resolver
            ->setRequired('domain_id')
            ->addAllowedTypes('domain_id', 'int')
            ->setDefaults($defaults);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function getCustomerUserDataBuilder(FormBuilderInterface $builder): FormBuilderInterface
    {
        $userDataBuilder = $builder->create('customerUserData', FormType::class, [
            'data_class' => CustomerUserData::class,
        ]);
        $userDataBuilder
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
                'constraints' => [
                    new Constraints\NotBlank(['message' => 'Please enter e-mail']),
                    new Email(['message' => 'Please enter valid e-mail']),
                    new Constraints\Length(['max' => 50, 'maxMessage' => 'Email cannot be longer than {{ limit }} characters']),
                    new UniqueEmail(['message' => 'This e-mail is already registered']),
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => [
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'first_options' => [
                    'constraints' => [
                        new Constraints\NotBlank(['message' => 'Please enter password']),
                        new Constraints\Length(['min' => 6, 'minMessage' => 'Password must be longer than {{ limit }} characters']),
                    ],
                ],
                'invalid_message' => 'Passwords do not match',
            ])
            ->add('memberOfBushmanClub', CheckboxType::class, [
                'required' => false,
            ])
            ->add('email2', HoneyPotType::class);

        return $userDataBuilder;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function getDeliveryAddressDataBuilder(FormBuilderInterface $builder, array $options): FormBuilderInterface
    {
        $deliveryAddressDataBuilder = $builder->create('deliveryAddressData', FormType::class, [
            'data_class' => DeliveryAddressData::class,
        ]);
        $deliveryAddressDataBuilder
            ->add('companyName', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Constraints\Length([
                        'max' => 100,
                        'maxMessage' => 'Company name cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_LOYALTY_PROGRAM_MEMBER],
                    ]),
                ],
            ])
            ->add('street', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter street',
                        'groups' => [self::VALIDATION_GROUP_LOYALTY_PROGRAM_MEMBER],
                    ]),
                    new Constraints\Length([
                        'max' => 100,
                        'maxMessage' => 'Street name cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_LOYALTY_PROGRAM_MEMBER],
                    ]),
                ],
            ])
            ->add('city', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter city',
                        'groups' => [self::VALIDATION_GROUP_LOYALTY_PROGRAM_MEMBER],
                    ]),
                    new Constraints\Length([
                        'max' => 100,
                        'maxMessage' => 'City name cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_LOYALTY_PROGRAM_MEMBER],
                    ]),
                ],
            ])
            ->add('postcode', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter zip code',
                        'groups' => [self::VALIDATION_GROUP_LOYALTY_PROGRAM_MEMBER],
                    ]),
                    new Constraints\Length([
                        'max' => 6,
                        'maxMessage' => 'Zip code cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_LOYALTY_PROGRAM_MEMBER],
                    ]),
                ],
            ]);
        $this->addCountryChoiceForGermanDomain($deliveryAddressDataBuilder, $options);

        return $deliveryAddressDataBuilder;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $deliveryAddressDataBuilder
     * @param array $options
     */
    private function addCountryChoiceForGermanDomain(FormBuilderInterface $deliveryAddressDataBuilder, array $options): void
    {
        if (DomainHelper::isGermanDomain($this->domain)) {
            $deliveryAddressDataBuilder
                ->add('country', ChoiceType::class, [
                    'required' => true,
                    'choices' => $this->countryFacade->getAllEnabledOnDomain($options['domain_id']),
                    'choice_label' => 'name',
                    'choice_value' => 'id',
                    'constraints' => [
                        new Constraints\NotBlank([
                            'message' => 'Please select country',
                            'groups' => [self::VALIDATION_GROUP_LOYALTY_PROGRAM_MEMBER],
                        ]),
                    ],
                ]);
        }
    }
}
