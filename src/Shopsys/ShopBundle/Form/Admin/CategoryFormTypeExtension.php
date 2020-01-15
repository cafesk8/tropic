<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FormTypesBundle\YesNoType;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Form\Admin\Category\CategoryFormType;
use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Form\Locale\LocalizedType;
use Shopsys\FrameworkBundle\Form\SortableValuesType;
use Shopsys\FrameworkBundle\Form\Transformers\RemoveDuplicatesFromArrayTransformer;
use Shopsys\ShopBundle\Component\Mall\MallFacade;
use Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade;
use Shopsys\ShopBundle\Model\Blog\Article\BlogArticlesIdsToBlogArticlesTransformer;
use Shopsys\ShopBundle\Model\Category\CategoryData;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryFormTypeExtension extends AbstractTypeExtension
{
    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade
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
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticlesIdsToBlogArticlesTransformer
     */
    private $blogArticlesIdsToBlogArticlesTransformer;

    /**
     * @var \Shopsys\ShopBundle\Component\Mall\MallFacade
     */
    private $mallFacade;

    /**
     * CategoryFormTypeExtension constructor.
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade $blogArticleFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabFacade
     * @param \Shopsys\FrameworkBundle\Form\Transformers\RemoveDuplicatesFromArrayTransformer $removeDuplicatesTransformer
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticlesIdsToBlogArticlesTransformer $blogArticlesIdsToBlogArticlesTransformer
     * @param \Shopsys\ShopBundle\Component\Mall\MallFacade $mallFacade
     */
    public function __construct(
        BlogArticleFacade $blogArticleFacade,
        AdminDomainTabsFacade $adminDomainTabFacade,
        RemoveDuplicatesFromArrayTransformer $removeDuplicatesTransformer,
        BlogArticlesIdsToBlogArticlesTransformer $blogArticlesIdsToBlogArticlesTransformer,
        MallFacade $mallFacade
    ) {
        $this->blogArticleFacade = $blogArticleFacade;
        $this->adminDomainTabsFacade = $adminDomainTabFacade;
        $this->removeDuplicatesTransformer = $removeDuplicatesTransformer;
        $this->blogArticlesIdsToBlogArticlesTransformer = $blogArticlesIdsToBlogArticlesTransformer;
        $this->mallFacade = $mallFacade;
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

        $builderSettingsGroup
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
