<?php

declare(strict_types=1);

namespace App\Form\Front\Customer;

use App\Component\Domain\DomainHelper;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Form\ValidationGroup;
use Shopsys\FrameworkBundle\Model\Country\CountryFacade;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressData;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class DeliveryAddressFormType extends AbstractType
{
    public const VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS = 'differentDeliveryAddress';

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
        $builder
            ->add('addressFilled', CheckboxType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'js-checkbox-toggle',
                    'data-checkbox-toggle-container-class' => 'js-delivery-address-fields',
                ],
            ])
            ->add('companyName', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Constraints\Length([
                        'max' => 100,
                        'maxMessage' => 'Company name cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS],
                    ]),
                ],
            ])
            ->add('street', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter street',
                        'groups' => [self::VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS],
                    ]),
                    new Constraints\Length([
                        'max' => 100,
                        'maxMessage' => 'Street name cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS],
                    ]),
                ],
            ])
            ->add('city', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter city',
                        'groups' => [self::VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS],
                    ]),
                    new Constraints\Length([
                        'max' => 100,
                        'maxMessage' => 'City name cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS],
                    ]),
                ],
            ])
            ->add('postcode', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Please enter zip code',
                        'groups' => [self::VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS],
                    ]),
                    new Constraints\Length([
                        'max' => 6,
                        'maxMessage' => 'Zip code cannot be longer than {{ limit }} characters',
                        'groups' => [self::VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS],
                    ]),
                ],
            ]);

        if (DomainHelper::isEnglishDomain($this->domain) === true) {
            /** @var \App\Model\Customer\User\CustomerUser $customerUser */
            $customerUser = $options['user'];
            if ($customerUser->getDefaultDeliveryAddress() === null || $customerUser->getDefaultDeliveryAddress()->getCountry() === null) {
                $countries = $this->countryFacade->getAllEnabledOnDomain($options['domain_id']);
            } else {
                $countries = [$customerUser->getDefaultDeliveryAddress()->getCountry()];
            }
        } else {
            $countries = [
                $this->countryFacade->findByCode(
                    DomainHelper::getCountryCodeByLocale($this->domain->getLocale())
                ),
            ];
        }

        $builder
            ->add('country', ChoiceType::class, [
                'required' => true,
                'choices' => $countries,
                'choice_label' => 'name',
                'choice_value' => 'id',
            ]);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('domain_id')
            ->addAllowedTypes('domain_id', 'int')
            ->setRequired('user')
            ->addAllowedTypes('user', CustomerUser::class)
            ->setDefaults([
                'data_class' => DeliveryAddressData::class,
                'attr' => ['novalidate' => 'novalidate'],
                'validation_groups' => function (FormInterface $form) {
                    $validationGroups = [ValidationGroup::VALIDATION_GROUP_DEFAULT];

                    /** @var \Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressData $deliveryAddressData */
                    $deliveryAddressData = $form->getData();

                    if ($deliveryAddressData->addressFilled) {
                        $validationGroups[] = self::VALIDATION_GROUP_DIFFERENT_DELIVERY_ADDRESS;
                    }

                    return $validationGroups;
                },
            ]);
    }
}
