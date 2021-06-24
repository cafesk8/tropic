<?php

declare(strict_types=1);

namespace App\Form\Front\Order;

use App\Model\Country\CountryFacade;
use App\Model\Order\FrontOrderData;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Form\Constraints\Email;
use Shopsys\FrameworkBundle\Form\DeliveryAddressChoiceType;
use Shopsys\FrameworkBundle\Form\ValidationGroup;
use Shopsys\FrameworkBundle\Model\Country\Country;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Heureka\HeurekaFacade;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class PersonalInfoFormType extends AbstractType
{
    public const VALIDATION_GROUP_COMPANY_CUSTOMER = 'companyCustomer';
    public const VALIDATION_GROUP_DELIVERY_ADDRESS_REQUIRED = 'deliveryAddressRequired';
    public const VALIDATION_GROUP_REGISTRATION_PASSWORD_REQUIRED = 'passwordRequired';

    private CurrentCustomerUser $currentCustomerUser;

    private CountryFacade $countryFacade;

    private HeurekaFacade $heurekaFacade;

    private Domain $domain;

    /**
     * @param \App\Model\Country\CountryFacade $countryFacade
     * @param \Shopsys\FrameworkBundle\Model\Heureka\HeurekaFacade $heurekaFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     */
    public function __construct(
        CountryFacade $countryFacade,
        HeurekaFacade $heurekaFacade,
        Domain $domain,
        CurrentCustomerUser $currentCustomerUser
    ) {
        $this->countryFacade = $countryFacade;
        $this->heurekaFacade = $heurekaFacade;
        $this->domain = $domain;
        $this->currentCustomerUser = $currentCustomerUser;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if($options['domain_id'] === Domain::FIRST_DOMAIN_ID) {
            $postCodeByDomainValidatorConstraint = new Constraints\Regex(
                ['pattern' => '/^[1-7]/', 'message' => 'Country postcode validation']
            );
        } else {
            $postCodeByDomainValidatorConstraint = new Constraints\Regex(
                ['pattern' => '/^[089]/', 'message' => 'Country postcode validation']
            );
        }

        $countries = $this->countryFacade->getAllEnabledOnDomain($options['domain_id']);

        $builder
            ->add('firstName', TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter first name',
                    ]),
                    new Constraints\Length([
                        'max' => 32,
                        'maxMessage' => 'First name cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter last name',
                    ]),
                    new Constraints\Length([
                        'max' => 30,
                        'maxMessage' => 'Last name cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Constraints\NotBlank(['message' => 'Please enter e-mail']),
                    new Email(['message' => 'Please enter valid e-mail']),
                    new Constraints\Length(['max' => 40, 'maxMessage' => 'Email cannot be longer than {{ limit }} characters']),
                ],
                'attr' => [
                    'class' => 'js-order-personal-info-form-email',
                ],
            ])
            ->add('registration', CheckboxType::class, ['required' => false])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => [
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'first_options' => [
                    'constraints' => [
                        new Constraints\NotBlank([
                            'groups' => [self::VALIDATION_GROUP_REGISTRATION_PASSWORD_REQUIRED],
                            'message' => 'Please enter password',
                        ]),
                        new Constraints\Length([
                            'min' => 6,
                            'minMessage' => 'Password must be longer than {{ limit }} characters',
                        ]),
                    ],
                ],
                'invalid_message' => 'Passwords do not match',
            ])
            ->add('telephone', TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter telephone number',
                    ]),
                    new Constraints\Length([
                        'max' => 20,
                        'maxMessage' => 'Telephone number cannot be longer than {{ limit }} characters',
                    ]),
                    new Constraints\Regex([
                        // https://regex101.com/r/iuaP2w/1
                        // https://en.wikipedia.org/wiki/E.164
                        'pattern' => '/^(?:\+(\d{1,3}))?(?:\d{3}){3}$/',
                        'message' => 'Telefonní číslo musí být platné',
                    ]),
                ],
            ])
            ->add('companyCustomer', CheckboxType::class, ['required' => false])
            ->add('companyName', TextType::class, [
                'attr' => ['autocomplete' => 'foxentry'],
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter company name',
                        'groups' => [self::VALIDATION_GROUP_COMPANY_CUSTOMER],
                    ]),
                    new Constraints\Length(['max' => 100,
                        'maxMessage' => 'Company name cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_COMPANY_CUSTOMER],
                    ]),
                ],
            ])
            ->add('companyNumber', TextType::class, [
                'attr' => ['autocomplete' => 'foxentry'],
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter identification number',
                        'groups' => [self::VALIDATION_GROUP_COMPANY_CUSTOMER],
                    ]),
                    new Constraints\Length([
                        'max' => 15,
                        'maxMessage' => 'Identification number cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_COMPANY_CUSTOMER],
                    ]),
                ],
            ])
            ->add('companyTaxNumber', TextType::class, [
                'attr' => ['autocomplete' => 'foxentry'],
                'required' => false,
                'constraints' => [
                    new Constraints\Length([
                        'max' => 18,
                        'maxMessage' => 'Tax number cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_COMPANY_CUSTOMER],
                    ]),
                ],
            ])
            ->add('street', TextType::class, [
                'attr' => ['autocomplete' => 'foxentry'],
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter street',
                        'groups' => [self::VALIDATION_GROUP_COMPANY_CUSTOMER],
                    ]),
                    new Constraints\Length([
                        'max' => 64,
                        'maxMessage' => 'Street name cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_COMPANY_CUSTOMER],
                    ]),
                ],
            ])
            ->add('city', TextType::class, [
                'attr' => ['autocomplete' => 'foxentry'],
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter city',
                        'groups' => [self::VALIDATION_GROUP_COMPANY_CUSTOMER],
                    ]),
                    new Constraints\Length([
                        'max' => 45,
                        'maxMessage' => 'City name cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_COMPANY_CUSTOMER],
                    ]),
                ],
            ])
            ->add('postcode', TextType::class, [
                'attr' => ['autocomplete' => 'foxentry'],
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter zip code',
                        'groups' => [self::VALIDATION_GROUP_COMPANY_CUSTOMER],
                    ]),
                    new Constraints\Length(['max' => 6, 'maxMessage' => 'Zip code cannot be longer than {{ limit }} characters']),
                    $postCodeByDomainValidatorConstraint,
                ],
            ]);

        $builder->add('country', ChoiceType::class, [
            'attr' => ['autocomplete' => 'foxentry'],
            'choices' => $countries,
            'choice_label' => 'name',
            'choice_value' => 'id',
            'constraints' => [
                new Constraints\NotBlank([
                    'message' => 'Please choose country',
                    'groups' => [self::VALIDATION_GROUP_COMPANY_CUSTOMER],
                ]),
            ],
        ]);

        if ($this->currentCustomerUser->findCurrentCustomerUser() !== null) {
            $builder->add('deliveryAddress', DeliveryAddressChoiceType::class, [
                'required' => false,
            ]);
        }

        $builder
            ->add('deliveryCompanyName', TextType::class, [
                'attr' => ['autocomplete' => 'foxentry'],
                'required' => false,
                'constraints' => [
                    new Constraints\Length([
                        'max' => 100,
                        'maxMessage' => 'Company name cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_DELIVERY_ADDRESS_REQUIRED],
                    ]),
                ],
            ])
            ->add('deliveryStreet', TextType::class, [
                'attr' => ['autocomplete' => 'foxentry'],
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter street',
                        'groups' => [self::VALIDATION_GROUP_DELIVERY_ADDRESS_REQUIRED],
                    ]),
                    new Constraints\Length([
                        'max' => 64,
                        'maxMessage' => 'Street name cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_DELIVERY_ADDRESS_REQUIRED],
                    ]),
                ],
            ])
            ->add('deliveryCity', TextType::class, [
                'attr' => ['autocomplete' => 'foxentry'],
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter city',
                        'groups' => [self::VALIDATION_GROUP_DELIVERY_ADDRESS_REQUIRED],
                    ]),
                    new Constraints\Length([
                        'max' => 45,
                        'maxMessage' => 'City name cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_DELIVERY_ADDRESS_REQUIRED],
                    ]),
                ],
            ])
            ->add('deliveryPostcode', TextType::class, [
                'attr' => ['autocomplete' => 'foxentry'],
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter zip code',
                        'groups' => [self::VALIDATION_GROUP_DELIVERY_ADDRESS_REQUIRED],
                    ]),
                    new Constraints\Length([
                        'max' => 6,
                        'maxMessage' => 'Zip code cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_DELIVERY_ADDRESS_REQUIRED],
                    ]),
                    $postCodeByDomainValidatorConstraint,
                ],
            ])
            ->add('deliveryCountry', ChoiceType::class, [
                'attr' => ['autocomplete' => 'foxentry'],
                'required' => true,
                'choices' => $countries,
                'data' => $this->countryFacade->getHackedCountry(),
                'disabled' => true,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please choose country',
                        'groups' => [self::VALIDATION_GROUP_DELIVERY_ADDRESS_REQUIRED],
                    ]),
                ],
            ])
            ->add('note', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Constraints\Length([
                        'max' => 240,
                        'maxMessage' => 'Poznámka nesmí být delší než {{ limit }} znaků',
                    ]),
                ],
            ])
            ->add('save', SubmitType::class);

        if ($this->heurekaFacade->isHeurekaShopCertificationActivated($this->domain->getId())) {
            $builder->add('disallowHeurekaVerifiedByCustomers', CheckboxType::class, [
                'label' => t('Nesouhlasím se zasláním dotazníku spokojenosti s nákupem od Heureky'),
                'required' => false,
            ]);
        }
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'order_personal_info_form';
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('domain_id')
            ->setAllowedTypes('domain_id', 'int')
            ->setRequired('country')
            ->setAllowedTypes('country', [Country::class, 'null'])
            ->setDefaults([
                'data_class' => FrontOrderData::class,
                'attr' => ['novalidate' => 'novalidate'],
                'validation_groups' => function (FormInterface $form) {
                    $validationGroups = [ValidationGroup::VALIDATION_GROUP_DEFAULT];

                    /** @var \App\Model\Order\FrontOrderData $orderData */
                    $orderData = $form->getData();

                    if ($orderData->registration) {
                        $validationGroups[] = self::VALIDATION_GROUP_REGISTRATION_PASSWORD_REQUIRED;
                    }

                    if ($orderData->companyCustomer) {
                        $validationGroups[] = self::VALIDATION_GROUP_COMPANY_CUSTOMER;
                    }

                    if ($this->isPickupPlaceAndStoreNull($orderData) && !$this->isPacketaTransport($orderData) && $orderData->deliveryAddress === null) {
                        $validationGroups[] = self::VALIDATION_GROUP_DELIVERY_ADDRESS_REQUIRED;
                    }

                    return $validationGroups;
                },
            ]);
    }

    /**
     * @param \App\Model\Order\FrontOrderData $orderData
     * @return bool
     */
    private function isPickupPlaceAndStoreNull(FrontOrderData $orderData): bool
    {
        if ($orderData->transport !== null && !$orderData->transport->isPickupPlaceType()) {
            return true;
        }

        return $orderData->pickupPlace === null && $orderData->store === null;
    }

    /**
     * @param \App\Model\Order\FrontOrderData $orderData
     * @return bool
     */
    private function isPacketaTransport(FrontOrderData $orderData): bool
    {
        return $orderData->transport !== null && $orderData->transport->isPacketaType();
    }
}
