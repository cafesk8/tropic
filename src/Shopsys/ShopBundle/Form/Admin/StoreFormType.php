<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Shopsys\FrameworkBundle\Form\DomainType;
use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Form\ImageUploadType;
use Shopsys\ShopBundle\Model\Store\Store;
use Shopsys\ShopBundle\Model\Store\StoreData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class StoreFormType extends AbstractType
{
    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
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
                        'message' => 'Please enter name of a store',
                    ]),
                    new Constraints\Length([
                        'max' => 100,
                        'maxMessage' => 'Store name cannot be longer than {{ limit }} characters',
                    ]),
                ],
                'label' => t('Name'),
            ])
            ->add('city', TextType::class, [
                'required' => false,
                'label' => t('City'),
                'constraints' => [
                    new Constraints\Length([
                        'max' => 100,
                        'maxMessage' => 'City name cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('street', TextType::class, [
                'required' => false,
                'label' => t('Street'),
                'constraints' => [
                    new Constraints\Length([
                        'max' => 100,
                        'maxMessage' => 'Street name cannot be longer than {{ limit }} characters',
                    ]),
                ],
            ])
            ->add('postcode', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Constraints\Length([
                        'max' => 30,
                        'maxMessage' => 'Zip code cannot be longer than {{ limit }} characters',
                    ]),
                ],
                'label' => t('Postcode'),
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

        $builderDescriptionGroup = $builder->create('description', GroupType::class, [
            'label' => t('Description'),
        ]);

        $builderDescriptionGroup
            ->add('description', CKEditorType::class, [
                'required' => false,
                'label' => t('Description'),
            ]);

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
                'entity' => $options['store'],
                'info_text' => t('You can upload following formats: PNG, JPG, GIF'),
                'label' => t('Image'),
            ]);

        $builder
            ->add($builderBasicInformationGroup)
            ->add($builderDescriptionGroup)
            ->add($builderImageGroup)
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
            ->setDefaults(
                [
                    'data_class' => StoreData::class,
                ]
            );
    }
}