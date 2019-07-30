<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Form\DomainType;
use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Form\ImageUploadType;
use Shopsys\FrameworkBundle\Model\Country\CountryFacade;
use Shopsys\ShopBundle\Model\Store\Store;
use Shopsys\ShopBundle\Model\Store\StoreData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class StoreFormType extends AbstractType
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Country\CountryFacade $countryFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     */
    public function __construct(
        CountryFacade $countryFacade,
        AdminDomainTabsFacade $adminDomainTabsFacade
    ) {
        $this->countryFacade = $countryFacade;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add($this->getBasicInformationGroup($builder))
            ->add($this->getAddressGroup($builder))
            ->add($this->getDescriptionGroup($builder))
            ->add($this->getImagesGroup($builder, $options['store']))
            ->add('save', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('store')
            ->setAllowedTypes('store', [Store::class, 'null'])
            ->setDefaults([
                'data_class' => StoreData::class,
                'attr' => ['novalidate' => 'novalidate'],
            ]);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function getBasicInformationGroup(FormBuilderInterface $builder): FormBuilderInterface
    {
        $builderBasicInformationGroup = $builder->create('basicInformation', GroupType::class, [
            'label' => t('Basic information'),
        ]);

        $builderBasicInformationGroup
            ->add('domainId', DomainType::class, [
                'required' => true,
                'label' => t('Domain'),
                'attr' => [
                    'class' => 'js-toggle-opt-group-control',
                ],
            ])
            ->add('name', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank([
                        'message' => 'Vyplňte, prosím, název prodejny',
                    ]),
                    new Constraints\Length([
                        'max' => 100,
                        'maxMessage' => 'Store name cannot be longer than {{ limit }} characters',
                    ]),
                ],
                'label' => t('Name'),
            ])
            ->add('openingHours', TextType::class, [
                'required' => false,
                'label' => t('Opening hours'),
                'constraints' => [
                    new Constraints\Length([
                        'max' => 100,
                        'maxMessage' => 'Opening hours cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('googleMapsLink', TextType::class, [
                'required' => false,
                'label' => t('Google Maps link'),
                'constraints' => [
                    new Constraints\Length([
                        'max' => 255,
                        'maxMessage' => 'Google Maps link cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('position', IntegerType::class, [
                'required' => false,
                'label' => t('Order in list'),
                'constraints' => [
                    new Constraints\Length([
                        'max' => 10,
                        'maxMessage' => 'Position in list cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ]);

        return $builderBasicInformationGroup;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function getAddressGroup(FormBuilderInterface $builder): FormBuilderInterface
    {
        $builderAddressGroup = $builder->create('address', GroupType::class, [
            'label' => t('Adresa'),
        ]);

        $builderAddressGroup->add('city', TextType::class, [
            'required' => true,
            'label' => t('City'),
            'constraints' => [
                new Constraints\NotBlank([
                    'message' => 'Vyplňte, prosím, město prodejny',
                ]),
                new Constraints\Length([
                    'max' => 100,
                    'maxMessage' => 'City name cannot be longer than {{ limit }} characters',
                ]),
            ],
        ])
        ->add('street', TextType::class, [
            'required' => true,
            'label' => t('Street'),
            'constraints' => [
                new Constraints\NotBlank([
                    'message' => 'Vyplňte, prosím, ulici prodejny',
                ]),
                new Constraints\Length([
                    'max' => 100,
                    'maxMessage' => 'Street name cannot be longer than {{ limit }} characters',
                ]),
            ],
        ])
        ->add('postcode', TextType::class, [
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank([
                    'message' => 'Vyplňte, prosím, PSČ prodejny',
                ]),
                new Constraints\Length([
                    'max' => 30,
                    'maxMessage' => 'Zip code cannot be longer than {{ limit }} characters',
                ]),
            ],
            'label' => t('Postcode'),
        ])
        ->add('country', ChoiceType::class, [
            'required' => true,
            'label' => t('Country'),
            'choices' => $this->countryFacade->getAllEnabledOnDomain($this->adminDomainTabsFacade->getSelectedDomainId()),
            'choice_label' => 'name',
            'choice_value' => 'id',
            'constraints' => [
                new Constraints\NotBlank([
                    'message' => 'Please enter country of a store',
                ]),
            ],
        ]);

        return $builderAddressGroup;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function getDescriptionGroup(FormBuilderInterface $builder): FormBuilderInterface
    {
        $builderDescriptionGroup = $builder->create('description', GroupType::class, [
            'label' => t('Description'),
        ]);

        $builderDescriptionGroup
            ->add('description', CKEditorType::class, [
                'required' => false,
                'label' => t('Description'),
            ]);

        return $builderDescriptionGroup;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \Shopsys\ShopBundle\Model\Store\Store|null $store
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function getImagesGroup(FormBuilderInterface $builder, ?Store $store): FormBuilderInterface
    {
        $builderImageGroup = $builder->create('image', GroupType::class, [
            'label' => t('Image'),
        ]);

        $builderImageGroup
            ->add('images', ImageUploadType::class, [
                'required' => false,
                'multiple' => false,
                'file_constraints' => [
                    new Constraints\Image([
                        'mimeTypes' => ['image/png', 'image/jpg', 'image/jpeg', 'image/gif'],
                        'mimeTypesMessage' => 'Image can be only in JPG, GIF or PNG format',
                        'maxSize' => '2M',
                        'maxSizeMessage' => 'Uploaded image is to large ({{ size }} {{ suffix }}). '
                            . 'Maximum size of an image is {{ limit }} {{ suffix }}.',
                    ]),
                ],
                'entity' => $store,
                'info_text' => t('You can upload following formats: PNG, JPG, GIF'),
                'label' => t('Image'),
            ]);

        return $builderImageGroup;
    }
}
