<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\Advert\Advert;
use App\Model\Advert\AdvertData;
use App\Model\Advert\AdvertPositionRegistry;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Form\Admin\Advert\AdvertFormType;
use Shopsys\FrameworkBundle\Form\CategoriesType;
use Shopsys\FrameworkBundle\Form\DisplayOnlyType;
use Shopsys\FrameworkBundle\Form\ImageUploadType;
use Shopsys\FrameworkBundle\Model\Advert\Advert as BaseAdvert;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class AdvertFormTypeExtension extends AbstractTypeExtension
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Model\Advert\AdvertPositionRegistry
     */
    private $advertPositionRegistry;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Advert\AdvertPositionRegistry $advertPositionRegistry
     */
    public function __construct(Domain $domain, AdvertPositionRegistry $advertPositionRegistry)
    {
        $this->domain = $domain;
        $this->advertPositionRegistry = $advertPositionRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $settingsGroup = $builder->get('settings');

        $settingsGroup->add('type', ChoiceType::class, [
            'required' => true,
            'choices' => [
                t('Image with link') => BaseAdvert::TYPE_IMAGE,
            ],
            'expanded' => true,
            'multiple' => false,
            'data' => BaseAdvert::TYPE_IMAGE,
            'constraints' => [
                new Constraints\NotBlank(['message' => 'Please choose advertisement type']),
            ],
            'label' => t('Type'),
            'attr' => [
                'container_class' => 'display-none',
            ],
        ]);

        $settingsGroup->add('categories', CategoriesType::class, [
            'attr' => [
                'class' => 'js-advert-categories-type',
            ],
            'domain_id' => $this->domain->getId(),
            'label' => t('Kategorie'),
            'position' => ['after' => 'positionName'],
            'required' => false,
        ]);

        $settingsGroup->add('name', TextType::class, [
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank(['message' => 'Vyplňte prosím text tlačítka']),
            ],
            'label' => t('Text tlačítka'),
        ]);

        $imagesGroup = $builder->get('image_group');

        foreach ($this->advertPositionRegistry->getImageSizeRecommendationsIndexedByNames() as $name => $imageSizeRecommendation) {
            $imagesGroup->add('imageSize-' . $name, DisplayOnlyType::class, [
                'attr' => [
                    'class' => 'js-image-size-recommendation js-image-size-recommendation-' . $name,
                ],
                'data' => $imageSizeRecommendation,
                'label' => t('Doporučené rozměry obrázku'),
            ]);
        }

        $this->addMobileImage($builder, $options);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    private function addMobileImage(FormBuilderInterface $builder, array $options): void
    {
        $imagesGroup = $builder->get('image_group');

        $imagesGroup->add('mobileImage', ImageUploadType::class, [
            'attr' => [
                'class' => 'js-mobile-image-input',
            ],
            'required' => false,
            'image_entity_class' => BaseAdvert::class,
            'file_constraints' => [
                new Constraints\Image([
                    'mimeTypes' => ['image/png', 'image/jpg', 'image/jpeg', 'image/gif'],
                    'mimeTypesMessage' => 'Image can be only in JPG, GIF or PNG format',
                    'maxSize' => '2M',
                    'maxSizeMessage' => 'Uploaded image is to large ({{ size }} {{ suffix }}). '
                        . 'Maximum size of an image is {{ limit }} {{ suffix }}.',
                ]),
            ],
            'label' => t('Mobilní verze'),
            'entity' => $options['advert'],
            'info_text' => t('You can upload following formats: PNG, JPG, GIF'),
            'image_type' => Advert::TYPE_MOBILE,
        ])->add('mobileImageSize', DisplayOnlyType::class, [
            'attr' => [
                'class' => 'js-mobile-image-size-recommendation',
            ],
            'data' => 'šířka: 429px, výška: 322px',
            'label' => t('Doporučené rozměry obrázku pro mobilní verzi'),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdvertData::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        yield AdvertFormType::class;
    }
}
