<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\Country\CountryFacade;
use App\Model\Store\Store;
use App\Model\Store\StoreData;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Shopsys\FormTypesBundle\YesNoType;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Form\DisplayOnlyType;
use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Form\ImageUploadType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class StoreFormType extends AbstractType
{
    /**
     * @var \App\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @param \App\Model\Country\CountryFacade $countryFacade
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
            ->add($this->getBasicInformationGroup($builder, $options['store']))
            ->add($this->getAdditionalInfoGroup($builder))
            ->add($this->getContactGroup($builder))
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
     * @param \App\Model\Store\Store $store
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function getBasicInformationGroup(FormBuilderInterface $builder, ?Store $store): FormBuilderInterface
    {
        $builderBasicInformationGroup = $builder->create('basicInformation', GroupType::class, [
            'label' => t('Basic information'),
        ]);

        $builderBasicInformationGroup
            ->add('name', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank(['message' => 'Vyplňte, prosím, název prodejny']),
                    new Constraints\Length(['max' => 100, 'maxMessage' => 'Store name cannot be longer than {{ limit }} characters']),
                ],
                'label' => t('Name'),
            ]);

        if ($store !== null) {
            $builderBasicInformationGroup
                ->add('position', DisplayOnlyType::class, [
                    'data' => $store->getPosition(),
                    'label' => t('Priorita'),
                ]);
        }

        $builderBasicInformationGroup
            ->add('pickupPlace', YesNoType::class, [
                'required' => false,
                'label' => t('Odběrné místo'),
            ])
            ->add('externalNumber', TextType::class, [
                'required' => false,
                'label' => t('Externí ID'),
                'constraints' => [
                    new Constraints\Length(['max' => 50, 'maxMessage' => 'Externí ID nesmí být delší než {{ limit }} znaků']),
                ],
            ])
            ->add('showOnStoreList', YesNoType::class, [
                'required' => false,
                'label' => t('Zobrazit na stránce prodejen'),
            ])
            ->add('centralStore', YesNoType::class, [
                'required' => false,
                'label' => t('Je sklad centrální'),
            ]);

        return $builderBasicInformationGroup;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function getAdditionalInfoGroup(FormBuilderInterface $builder): FormBuilderInterface
    {
        $infoGroup = $builder->create('additionalInfo', GroupType::class, [
            'label' => t('Další informace'),
        ]);

        $infoGroup
            ->add('openingHours', TextType::class, [
                'required' => false,
                'label' => t('Opening hours'),
                'constraints' => [
                    new Constraints\Length(['max' => 100, 'maxMessage' => 'Opening hours cannot be longer than {{ limit }} characters']),
                ],
            ])
            ->add('googleMapsLink', TextType::class, [
                'required' => false,
                'label' => t('Google Maps link'),
                'constraints' => [
                    new Constraints\Length(['max' => 500, 'maxMessage' => 'Google Maps link cannot be longer than {{ limit }} characters']),
                ],
            ]);

        return $infoGroup;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function getContactGroup(FormBuilderInterface $builder): FormBuilderInterface
    {
        $builderContactGroup = $builder->create('contact', GroupType::class, [
            'label' => t('Kontakt'),
        ]);

        $builderContactGroup
            ->add('email', EmailType::class, [
                'required' => false,
                'label' => t('E-mail'),
                'constraints' => [
                    new Constraints\Length(['max' => 255, 'maxMessage' => 'Email cannot be longer than {{ limit }} characters']),
                    new Constraints\Email(['message' => 'Please enter valid e-mail']),
                ],
            ])
            ->add('telephone', TextType::class, [
                'required' => false,
                'label' => t('Telefon'),
                'constraints' => [
                    new Constraints\Length(['max' => 30, 'maxMessage' => 'Telephone number cannot be longer than {{ limit }} characters']),
                ],
            ]);

        return $builderContactGroup;
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
                new Constraints\NotBlank(['message' => 'Vyplňte, prosím, město prodejny']),
                new Constraints\Length(['max' => 100, 'maxMessage' => 'City name cannot be longer than {{ limit }} characters']),
            ],
        ])
            ->add('street', TextType::class, [
                'required' => true,
                'label' => t('Street'),
                'constraints' => [
                    new Constraints\NotBlank(['message' => 'Vyplňte, prosím, ulici prodejny']),
                    new Constraints\Length(['max' => 100, 'maxMessage' => 'Street name cannot be longer than {{ limit }} characters']),
                ],
            ])
            ->add('postcode', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank(['message' => 'Vyplňte, prosím, PSČ prodejny']),
                    new Constraints\Length(['max' => 30, 'maxMessage' => 'Zip code cannot be longer than {{ limit }} characters']),
                ],
                'label' => t('Postcode'),
            ])
            ->add('region', TextType::class, [
                'required' => false,
                'label' => t('Kraj'),
                'constraints' => [
                    new Constraints\Length(['max' => 200, 'maxMessage' => 'Region nesmí být delší než {{ limit }} znaků']),
                ],
            ])
            ->add('country', ChoiceType::class, [
                'required' => true,
                'label' => t('Country'),
                'choices' => $this->countryFacade->getAllEnabledOnDomain($this->adminDomainTabsFacade->getSelectedDomainId()),
                'choice_label' => 'name',
                'choice_value' => 'id',
                'constraints' => [
                    new Constraints\NotBlank(['message' => 'Please enter country of a store']),
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
     * @param \App\Model\Store\Store|null $store
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
