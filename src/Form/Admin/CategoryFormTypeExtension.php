<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Component\Form\FormBuilderHelper;
use App\Component\Mall\MallFacade;
use App\Model\Blog\Article\BlogArticleFacade;
use App\Model\Blog\Article\BlogArticlesIdsToBlogArticlesTransformer;
use App\Model\Category\CategoryData;
use App\Twig\DateTimeFormatterExtension;
use Shopsys\FormTypesBundle\YesNoType;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Form\Admin\Category\CategoryFormType;
use Shopsys\FrameworkBundle\Form\DisplayOnlyType;
use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Form\Locale\LocalizedType;
use Shopsys\FrameworkBundle\Form\SortableValuesType;
use Shopsys\FrameworkBundle\Form\Transformers\RemoveDuplicatesFromArrayTransformer;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryFormTypeExtension extends AbstractTypeExtension
{
    public const DISABLED_FIELDS = [
        'pohodaId',
        'name',
        'listable',
        'updatedByPohodaAt',
    ];

    /**
     * @var \App\Component\Form\FormBuilderHelper
     */
    protected $formBuilderHelper;

    /**
     * @var \App\Model\Blog\Article\BlogArticleFacade
     */
    private $blogArticleFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Form\Transformers\RemoveDuplicatesFromArrayTransformer
     */
    private $removeDuplicatesTransformer;

    /**
     * @var \App\Model\Blog\Article\BlogArticlesIdsToBlogArticlesTransformer
     */
    private $blogArticlesIdsToBlogArticlesTransformer;

    /**
     * @var \App\Component\Mall\MallFacade
     */
    private $mallFacade;

    /**
     * @var \App\Twig\DateTimeFormatterExtension
     */
    private $dateTimeFormatterExtension;

    /**
     * CategoryFormTypeExtension constructor.
     * @param \App\Model\Blog\Article\BlogArticleFacade $blogArticleFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabFacade
     * @param \Shopsys\FrameworkBundle\Form\Transformers\RemoveDuplicatesFromArrayTransformer $removeDuplicatesTransformer
     * @param \App\Model\Blog\Article\BlogArticlesIdsToBlogArticlesTransformer $blogArticlesIdsToBlogArticlesTransformer
     * @param \App\Component\Mall\MallFacade $mallFacade
     * @param \App\Twig\DateTimeFormatterExtension $dateTimeFormatterExtension
     * @param \App\Component\Form\FormBuilderHelper $formBuilderHelper
     */
    public function __construct(
        BlogArticleFacade $blogArticleFacade,
        AdminDomainTabsFacade $adminDomainTabFacade,
        RemoveDuplicatesFromArrayTransformer $removeDuplicatesTransformer,
        BlogArticlesIdsToBlogArticlesTransformer $blogArticlesIdsToBlogArticlesTransformer,
        MallFacade $mallFacade,
        DateTimeFormatterExtension $dateTimeFormatterExtension,
        FormBuilderHelper $formBuilderHelper
    ) {
        $this->blogArticleFacade = $blogArticleFacade;
        $this->adminDomainTabsFacade = $adminDomainTabFacade;
        $this->removeDuplicatesTransformer = $removeDuplicatesTransformer;
        $this->blogArticlesIdsToBlogArticlesTransformer = $blogArticlesIdsToBlogArticlesTransformer;
        $this->mallFacade = $mallFacade;
        $this->dateTimeFormatterExtension = $dateTimeFormatterExtension;
        $this->formBuilderHelper = $formBuilderHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $blogArticles = $this->blogArticleFacade->getAllBlogArticlesNamesIndexedByIdByDomainId(
            $this->adminDomainTabsFacade->getSelectedDomainId(),
            $this->adminDomainTabsFacade->getSelectedDomainConfig()->getLocale()
        );

        $builderSettingsGroup = $builder->get('settings');
        /** @var \App\Model\Category\Category|null $category */
        $category = $options['category'];

        $builderSettingsGroup
            ->add('updatedByPohodaAt', DisplayOnlyType::class, [
                'data' => $category !== null ? $this->dateTimeFormatterExtension->formatDateTime($category->getUpdatedByPohodaAt()) : '-',
                'label' => t('Poslední aktualizace z IS'),
            ])
            ->add('pohodaId', DisplayOnlyType::class, [
                'data' => $category !== null && $category->getPohodaId() !== null ? $category->getPohodaId() : '-',
                'label' => t('Pohoda ID'),
            ])
            ->add('listable', YesNoType::class, [
                'required' => false,
                'label' => t('Zobrazovat v menu a dalších výpisech'),
            ])
            ->add('displayedInHorizontalMenu', YesNoType::class, [
                'required' => false,
                'label' => t('V hlavním menu'),
            ])
            ->add('displayedInFirstColumn', YesNoType::class, [
                'required' => false,
                'label' => t('V prvním sloupci'),
            ])
            ->add('preListingCategory', YesNoType::class, [
                'required' => false,
                'label' => t('Předvýpis kategorií'),
            ])
            ->add('legendaryCategory', YesNoType::class, [
                'required' => false,
                'label' => t('Legendární kategorie'),
            ])
            ->add('leftBannerTexts', LocalizedType::class, [
                'required' => false,
                'label' => t('Levý textový baner dole'),
                'entry_options' => [
                    'attr' => [
                        'class' => 'js-category-left-banner-texts',
                    ],
                ],
            ])
            ->add('rightBannerTexts', LocalizedType::class, [
                'required' => false,
                'label' => t('Pravý textový baner nahoře'),
                'entry_options' => [
                    'attr' => [
                        'class' => 'js-category-right-banner-texts',
                    ],
                ],
            ])
            ->add(
                $builder
                    ->create('blogArticles', SortableValuesType::class, [
                        'label' => 'Články blogu',
                        'labels_by_value' => $blogArticles,
                        'required' => false,
                    ])
                    ->addViewTransformer($this->removeDuplicatesTransformer)
                    ->addModelTransformer($this->blogArticlesIdsToBlogArticlesTransformer)
            );

        $builder->add($this->createMallGroup($builder));
        $this->formBuilderHelper->disableFieldsByConfigurations($builder, self::DISABLED_FIELDS);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => CategoryData::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CategoryFormType::class;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function createMallGroup(FormBuilderInterface $builder): FormBuilderInterface
    {
        $builderMallGroup = $builder->create('mall', GroupType::class, [
            'label' => t('Mall.cz'),
        ]);

        $mallCategories = $this->mallFacade->getCategories();

        $builderMallGroup->add('mallCategoryId', ChoiceType::class, [
            'choices' => array_flip($mallCategories),
            'required' => false,
            'label' => t('Kategorie v Mall.cz'),
        ]);

        return $builderMallGroup;
    }
}
