<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Form\BlogCategoriesType;
use App\Model\Blog\Article\BlogArticle;
use App\Model\Blog\Article\BlogArticleData;
use DateTime;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Shopsys\FormTypesBundle\MultidomainType;
use Shopsys\FormTypesBundle\YesNoType;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Form\DateTimeType;
use Shopsys\FrameworkBundle\Form\FormRenderingConfigurationExtension;
use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Form\ImageUploadType;
use Shopsys\FrameworkBundle\Form\Locale\LocalizedType;
use Shopsys\FrameworkBundle\Form\ProductsType;
use Shopsys\FrameworkBundle\Form\Transformers\RemoveDuplicatesFromArrayTransformer;
use Shopsys\FrameworkBundle\Form\UrlListType;
use Shopsys\FrameworkBundle\Model\Seo\SeoSettingFacade;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class BlogArticleFormType extends AbstractType
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Seo\SeoSettingFacade
     */
    private $seoSettingFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Form\Transformers\RemoveDuplicatesFromArrayTransformer
     */
    private $removeDuplicatesTransformer;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Seo\SeoSettingFacade $seoSettingFacade
     * @param \Shopsys\FrameworkBundle\Form\Transformers\RemoveDuplicatesFromArrayTransformer $removeDuplicatesTransformer
     */
    public function __construct(
        Domain $domain,
        SeoSettingFacade $seoSettingFacade,
        RemoveDuplicatesFromArrayTransformer $removeDuplicatesTransformer
    ) {
        $this->domain = $domain;
        $this->seoSettingFacade = $seoSettingFacade;
        $this->removeDuplicatesTransformer = $removeDuplicatesTransformer;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var \App\Model\Blog\Article\BlogArticle|null $blogArticle */
        $blogArticle = $options['blogArticle'];

        $builderSettingsGroup = $this->createSettingsGroup($builder, $blogArticle);
        $builderSeoGroup = $this->createSeoGroup($builder, $blogArticle);
        $builderDescriptionGroup = $this->createDescriptionGroup($builder);
        $builderImageGroup = $this->createImageGroup($builder, $options);
        $builderPerexGroup = $this->createPerexGroup($builder);
        $mainPhotoTitleGroup = $this->createMainPhotoTitleGroup($builder);
        $builderProductSet = $this->createProductSet($builder);

        $builder
            ->add($builderSettingsGroup)
            ->add($builderSeoGroup)
            ->add($builderPerexGroup)
            ->add($builderDescriptionGroup)
            ->add($mainPhotoTitleGroup)
            ->add($builderImageGroup)
            ->add($builderProductSet)
            ->add('save', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['blogArticle'])
            ->setAllowedTypes('blogArticle', [BlogArticle::class, 'null'])
            ->setDefaults([
                'data_class' => BlogArticleData::class,
                'attr' => ['novalidate' => 'novalidate'],
            ]);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param \App\Model\Blog\Article\BlogArticle|null $blogArticle
     * @return string|null
     */
    private function getArticleNameForPlaceholder(DomainConfig $domainConfig, ?BlogArticle $blogArticle = null): ?string
    {
        $domainLocale = $domainConfig->getLocale();

        return $blogArticle === null ? '' : $blogArticle->getName($domainLocale);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Blog\Article\BlogArticle|null $blogArticle
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createSeoGroup(FormBuilderInterface $builder, ?BlogArticle $blogArticle): FormBuilderInterface
    {
        list($seoTitlesOptionsByDomainId, $seoMetaDescriptionsOptionsByDomainId, $seoH1OptionsByDomainId) = $this->prepareSeoData($blogArticle);

        $builderSeoGroup = $builder->create('seo', GroupType::class, [
            'label' => t('Seo'),
        ]);

        $builderSeoGroup
            ->add('seoTitles', MultidomainType::class, [
                'entry_type' => TextType::class,
                'required' => false,
                'options_by_domain_id' => $seoTitlesOptionsByDomainId,
                'macro' => [
                    'name' => 'seoFormRowMacros.multidomainRow',
                    'recommended_length' => 60,
                ],
                'label' => t('Page title'),
            ])
            ->add('seoMetaDescriptions', MultidomainType::class, [
                'entry_type' => TextareaType::class,
                'required' => false,
                'options_by_domain_id' => $seoMetaDescriptionsOptionsByDomainId,
                'macro' => [
                    'name' => 'seoFormRowMacros.multidomainRow',
                    'recommended_length' => 155,
                ],
                'label' => t('Meta description'),
            ])
            ->add('seoH1s', MultidomainType::class, [
                'required' => false,
                'entry_options' => [
                    'constraints' => [
                        new Constraints\Length(['max' => 255, 'maxMessage' => 'Heading (H1) cannot be longer than {{ limit }} characters']),
                    ],
                ],
                'options_by_domain_id' => $seoH1OptionsByDomainId,
                'macro' => [
                    'name' => 'seoFormRowMacros.multidomainRow',
                    'recommended_length' => null,
                ],
                'label' => t('Heading (H1)'),
            ]);

        if ($blogArticle !== null) {
            $builderSeoGroup
                ->add('urls', UrlListType::class, [
                    'route_name' => 'front_blogarticle_detail',
                    'entity_id' => $blogArticle->getId(),
                    'label' => t('URL addresses'),
                ]);
        }

        return $builderSeoGroup;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Blog\Article\BlogArticle|null $blogArticle
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createSettingsGroup(FormBuilderInterface $builder, ?BlogArticle $blogArticle): FormBuilderInterface
    {
        $builderSettingsGroup = $builder->create('settings', GroupType::class, [
            'label' => t('Settings'),
        ]);

        $categoriesOptionsByDomainId = [];
        foreach ($this->domain->getAllIds() as $domainId) {
            $categoriesOptionsByDomainId[$domainId] = [
                'domain_id' => $domainId,
            ];
        }

        $builderSettingsGroup
            ->add('names', LocalizedType::class, [
                'main_constraints' => [
                    new Constraints\NotBlank(['message' => 'Please enter name']),
                ],
                'entry_options' => [
                    'required' => false,
                    'constraints' => [
                        new Constraints\Length(['max' => 255, 'maxMessage' => 'Name cannot be longer than {{ limit }} characters']),
                    ],
                ],
                'label' => t('Name'),
            ])
            ->add('blogCategoriesByDomainId', MultidomainType::class, [
                'required' => false,
                'entry_type' => BlogCategoriesType::class,
                'options_by_domain_id' => $categoriesOptionsByDomainId,
                'label' => t('Assign to category'),
                'display_format' => FormRenderingConfigurationExtension::DISPLAY_FORMAT_MULTIDOMAIN_ROWS_NO_PADDING,
            ])
            ->add('hidden', YesNoType::class, [
                'required' => false,
                'label' => t('Hide'),
            ])
            ->add('visibleOnHomepage', YesNoType::class, [
                'required' => true,
                'label' => t('Viditeln?? na homepage'),
            ])
            ->add('publishDate', DateTimeType::class, [
                'required' => true,
                'constraints' => [
                    new Constraints\NotBlank(['message' => 'Please enter date of creation']),
                ],
                'label' => t('Datum publikace'),
                'data' => $blogArticle === null ? new DateTime() : $blogArticle->getPublishDate(),
                'invalid_message' => 'Zadejte datum a ??as ve form??tu DD.MM.RRRR HH:MM:SS (nap????klad 24.02.2020 14:05:34)',
            ]);

        return $builderSettingsGroup;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createDescriptionGroup(FormBuilderInterface $builder): FormBuilderInterface
    {
        $builderDescriptionGroup = $builder->create('description', GroupType::class, [
            'label' => t('Description'),
        ]);

        $builderDescriptionGroup
            ->add('descriptions', LocalizedType::class, [
                'entry_type' => CKEditorType::class,
                'label' => t('Description'),
                'required' => false,
                'display_format' => FormRenderingConfigurationExtension::DISPLAY_FORMAT_MULTIDOMAIN_ROWS_NO_PADDING,
            ]);

        return $builderDescriptionGroup;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createPerexGroup(FormBuilderInterface $builder): FormBuilderInterface
    {
        $builderDescriptionGroup = $builder->create('perex', GroupType::class, [
            'label' => t('Perex'),
        ]);

        $builderDescriptionGroup
            ->add('perexes', LocalizedType::class, [
                'entry_options' => [
                    'required' => false,
                ],
                'entry_type' => TextareaType::class,
                'label' => t('Perex'),

            ]);
        return $builderDescriptionGroup;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createMainPhotoTitleGroup(FormBuilderInterface $builder): FormBuilderInterface
    {
        $builderDescriptionGroup = $builder->create('mainPhotoTitlesGroup', GroupType::class, [
            'label' => t('Popisy hlavn??ch obr??zk??'),
        ]);

        $builderDescriptionGroup
            ->add('mainPhotoTitles', LocalizedType::class, [
                'entry_options' => [
                    'required' => false,
                    'constraints' => [
                        new Constraints\Length(['max' => 255, 'maxMessage' => 'Popis nem????e m??t v??ce ne?? {{ limit }} znak??']),
                    ],
                ],
                'entry_type' => TextareaType::class,
                'required' => false,
                'label' => t('Popisy hlavn??ch obr??zk??'),

            ]);
        return $builderDescriptionGroup;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createImageGroup(FormBuilderInterface $builder, array $options): FormBuilderInterface
    {
        $builderImageGroup = $builder->create('images', GroupType::class, [
            'label' => t('Obr??zky'),
        ]);

        $builderImageGroup
            ->add('images', ImageUploadType::class, [
                'required' => false,
                'multiple' => true,
                'file_constraints' => [
                    new Constraints\Image([
                        'mimeTypes' => ['image/png', 'image/jpg', 'image/jpeg', 'image/gif'],
                        'mimeTypesMessage' => 'Image can be only in JPG, GIF or PNG format',
                        'maxSize' => '2M',
                        'maxSizeMessage' => 'Uploaded image is to large ({{ size }} {{ suffix }}). '
                            . 'Maximum size of an image is {{ limit }} {{ suffix }}.',
                    ]),
                ],
                'label' => t('Upload image'),
                'entity' => $options['blogArticle'],
                'info_text' => t('You can upload following formats: PNG, JPG, GIF'),
            ]);

        return $builderImageGroup;
    }

    /**
     * @param \App\Model\Blog\Article\BlogArticle|null $blogArticle
     * @return array
     */
    private function prepareSeoData(?BlogArticle $blogArticle): array
    {
        $seoTitlesOptionsByDomainId = [];
        $seoMetaDescriptionsOptionsByDomainId = [];
        $seoH1OptionsByDomainId = [];

        foreach ($this->domain->getAll() as $domainConfig) {
            $domainId = $domainConfig->getId();

            $seoTitlesOptionsByDomainId[$domainId] = [
                'attr' => [
                    'placeholder' => $this->getArticleNameForPlaceholder($domainConfig, $blogArticle),
                    'class' => 'js-dynamic-placeholder',
                    'data-placeholder-source-input-id' => 'blog_article_form_name_' . $domainConfig->getLocale(),
                ],
            ];
            $seoMetaDescriptionsOptionsByDomainId[$domainId] = [
                'attr' => [
                    'placeholder' => $this->seoSettingFacade->getDescriptionMainPage($domainId),
                ],
            ];
            $seoH1OptionsByDomainId[$domainId] = [
                'attr' => [
                    'placeholder' => $this->getArticleNameForPlaceholder($domainConfig, $blogArticle),
                    'class' => 'js-dynamic-placeholder',
                    'data-placeholder-source-input-id' => 'blog_article_form_name_' . $domainConfig->getLocale(),
                ],
            ];
        }

        return [$seoTitlesOptionsByDomainId, $seoMetaDescriptionsOptionsByDomainId, $seoH1OptionsByDomainId];
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createProductSet(FormBuilderInterface $builder): FormBuilderInterface
    {
        $builderProductSet = $builder->create('products', GroupType::class, [
            'label' => t('Produkty k ??l??nku blogu'),
        ]);

        $builderProductSet
            ->add('products', ProductsType::class, [
                'required' => false,
                'allow_main_variants' => true,
                'allow_variants' => true,
            ])
            ->addViewTransformer($this->removeDuplicatesTransformer);

        return $builderProductSet;
    }
}
