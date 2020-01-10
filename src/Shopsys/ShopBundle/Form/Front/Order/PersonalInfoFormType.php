<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Front\Order;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Form\Constraints\Email;
use Shopsys\FrameworkBundle\Form\Transformers\InverseTransformer;
use Shopsys\FrameworkBundle\Form\ValidationGroup;
use Shopsys\FrameworkBundle\Model\Country\Country;
use Shopsys\FrameworkBundle\Model\Country\CountryFacade;
use Shopsys\FrameworkBundle\Model\Heureka\HeurekaFacade;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Model\Order\FrontOrderData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
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
    public const VALIDATION_GROUP_BILLING_ADDRESS_FILLED = 'billingAddressFilled';
    public const VALIDATION_GROUP_DELIVERY_ADDRESS_REQUIRED = 'deliveryAddressRequired';
    public const VALIDATION_GROUP_PHONE_PLUS_REQUIRED = 'phonePlusRequired';

    /**
     * @var \Shopsys\ShopBundle\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Heureka\HeurekaFacade
     */
    private $heurekaFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Country\CountryFacade $countryFacade
     * @param \Shopsys\FrameworkBundle\Model\Heureka\HeurekaFacade $heurekaFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(CountryFacade $countryFacade, HeurekaFacade $heurekaFacade, Domain $domain)
    {
        $this->countryFacade = $countryFacade;
        $this->heurekaFacade = $heurekaFacade;
        $this->domain = $domain;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $countries = $this->countryFacade->getAllEnabledOnDomain($options['domain_id']);

        $builder
            ->add('firstName', TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter first name',
                    ]),
                    new Constraints\Length([
                        'max' => 60,
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
                    new Constraints\Length(['max' => 50, 'maxMessage' => 'Email cannot be longer than {{ limit }} characters']),
                ],
                'attr' => [
                    'class' => 'js-order-personal-info-form-email',
                ],
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
                        'pattern' => '/^\+{1}[^\+]+$/',
                        'message' => 'Telefonní číslo musí začínat znakem +',
                        'groups' => [self::VALIDATION_GROUP_PHONE_PLUS_REQUIRED],
                    ]),
                    new Constraints\Regex([
                        // https://regex101.com/r/tyKloi/1
                        // https://en.wikipedia.org/wiki/E.164
                        'pattern' => '/^\+?[1-9]\d{1,14}$/',
                        'message' => 'Telefonní číslo musí být platné',
                    ]),
                ],
            ])
            ->add('companyCustomer', CheckboxType::class, ['required' => false])
            ->add('companyName', TextType::class, [
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
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter identification number',
                        'groups' => [self::VALIDATION_GROUP_COMPANY_CUSTOMER],
                    ]),
                    new Constraints\Length([
                        'max' => 20,
                        'maxMessage' => 'Identification number cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_COMPANY_CUSTOMER],
                    ]),
                ],
            ])
            ->add('companyTaxNumber', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Constraints\Length([
                        'max' => 30,
                        'maxMessage' => 'Tax number cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_COMPANY_CUSTOMER],
                    ]),
                ],
            ])
            ->add('street', TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter street',
                        'groups' => [self::VALIDATION_GROUP_BILLING_ADDRESS_FILLED],
                    ]),
                    new Constraints\Length([
                        'max' => 100,
                        'maxMessage' => 'Street name cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_BILLING_ADDRESS_FILLED],
                    ]),
                ],
            ])
            ->add('city', TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter city',
                        'groups' => [self::VALIDATION_GROUP_BILLING_ADDRESS_FILLED],
                    ]),
                    new Constraints\Length([
                        'max' => 100,
                        'maxMessage' => 'City name cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_BILLING_ADDRESS_FILLED],
                    ]),
                ],
            ])
            ->add('postcode', TextType::class, [
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter zip code',
                        'groups' => [self::VALIDATION_GROUP_BILLING_ADDRESS_FILLED],
                    ]),
                    new Constraints\Length(['max' => 6, 'maxMessage' => 'Zip code cannot be longer than {{ limit }} characters']),
                ],
            ]);

        $builder->add('country', ChoiceType::class, [
                'choices' => $countries,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please choose country',
                        'groups' => [self::VALIDATION_GROUP_BILLING_ADDRESS_FILLED],
                    ]),
                ],
            ]);

        $builder->add($builder
                ->create('billingAddressFilled', CheckboxType::class, [
                    'required' => false,
                    'value' => false,
                    'property_path' => 'deliveryAddressSameAsBillingAddress',
                ])
                ->addModelTransformer(new InverseTransformer()))
            ->add('deliveryCompanyName', TextType::class, [
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
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter street',
                        'groups' => [self::VALIDATION_GROUP_DELIVERY_ADDRESS_REQUIRED],
                    ]),
                    new Constraints\Length([
                        'max' => 100,
                        'maxMessage' => 'Street name cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_DELIVERY_ADDRESS_REQUIRED],
                    ]),
                ],
            ])
            ->add('deliveryCity', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter city',
                        'groups' => [self::VALIDATION_GROUP_DELIVERY_ADDRESS_REQUIRED],
                    ]),
                    new Constraints\Length([
                        'max' => 100,
                        'maxMessage' => 'City name cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_DELIVERY_ADDRESS_REQUIRED],
                    ]),
                ],
            ])
            ->add('deliveryPostcode', TextType::class, [
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
                ],
            ])
            ->add('deliveryCountry', ChoiceType::class, [
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
                        'max' => 1000,
                        'maxMessage' => 'Poznámka nesmí být delší než {{ limit }} znaků',
                    ]),
                ],
            ])
            ->add('save', SubmitType::class);

        if ($this->heurekaFacade->isHeurekaShopCertificationActivated($this->domain->getId())) {
            $builder->add('disallowHeurekaVerifiedByCustomers', CheckboxType::class, [
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

                    /** @var \Shopsys\ShopBundle\Model\Order\FrontOrderData $orderData */
                    $orderData = $form->getData();

                    if ($orderData->companyCustomer) {
                        $validationGroups[] = self::VALIDATION_GROUP_COMPANY_CUSTOMER;
                    }
                    if (!$orderData->deliveryAddressSameAsBillingAddress) {
                        $validationGroups[] = self::VALIDATION_GROUP_BILLING_ADDRESS_FILLED;
                    }

                    if ($this->isPickupPlaceAndStoreNull($orderData) === true) {
                        $validationGroups[] = self::VALIDATION_GROUP_DELIVERY_ADDRESS_REQUIRED;
                    }

                    if (DomainHelper::isGermanDomain($this->domain)) {
                        $validationGroups[] = self::VALIDATION_GROUP_PHONE_PLUS_REQUIRED;
                    }

                    return $validationGroups;
                },
            ]);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\FrontOrderData $orderData
     * @return bool
     */
    private function isPickupPlaceAndStoreNull(FrontOrderData $orderData): bool
    {
        return $orderData->pickupPlace === null && $orderData->store === null;
    }
}
