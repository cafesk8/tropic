<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FormTypesBundle\MultidomainType;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Form\Admin\Product\ProductFormType;
use Shopsys\FrameworkBundle\Form\Constraints\NotNegativeMoneyAmount;
use Shopsys\FrameworkBundle\Form\DisplayOnlyType;
use Shopsys\FrameworkBundle\Form\DisplayOnlyUrlType;
use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Form\ProductsType;
use Shopsys\FrameworkBundle\Form\Transformers\RemoveDuplicatesFromArrayTransformer;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFacade;
use Shopsys\FrameworkBundle\Twig\PriceExtension;
use Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade;
use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductData;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class ProductFormTypeExtension extends AbstractTypeExtension
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade
     */
    private $blogArticleFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Twig\PriceExtension
     */
    private $priceExtension;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * ProductFormTypeExtension constructor.
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFacade $parameterFacade
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade $blogArticleFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \Shopsys\FrameworkBundle\Twig\PriceExtension $priceExtension
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        ParameterFacade $parameterFacade,
        BlogArticleFacade $blogArticleFacade,
        AdminDomainTabsFacade $adminDomainTabsFacade,
        PriceExtension $priceExtension,
        Domain $domain
    ) {
        $this->parameterFacade = $parameterFacade;
        $this->blogArticleFacade = $blogArticleFacade;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
        $this->priceExtension = $priceExtension;
        $this->domain = $domain;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $product = $options['product'];
        /* @var $product \Shopsys\FrameworkBundle\Model\Product\Product|null */

        $builderStoreStockGroup = $builder->create('storeStock', GroupType::class, [
            'label' => t('Stock in stores'),
        ]);

        $builderStoreStockGroup->add('stockQuantityByStoreId', StoreStockType::class);

        $builder->add($builderStoreStockGroup);

        if ($product !== null) {
            $builder->add($this->getArticlesGroup($builder, $product));
        }

        if ($product instanceof Product && $product->isMainVariant()) {
            $variantGroup = $builder->get('variantGroup');

            $allParameters = $this->parameterFacade->getAll();
            $variantGroup
                ->add('distinguishingParameter', ChoiceType::class, [
                    'required' => false,
                    'label' => t('Rozlišující parametr'),
                    'choices' => $allParameters,
                    'choice_label' => 'name',
                    'choice_value' => 'id',
                    'placeholder' => t('Zvolte parametr'),
                    'constraints' => [
                        new Constraints\NotBlank(),
                    ],
                ]);

            $builder->add($variantGroup);
        }

        if ($product !== null && $product->getMainVariantGroup() !== null) {
            $this->createMainVariantGroup($builder);
            $this->addActionPriceToPricesGroup($builder);
        }
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    public function getArticlesGroup(FormBuilderInterface $builder, Product $product): FormBuilderInterface
    {
        $locale = $this->adminDomainTabsFacade->getSelectedDomainConfig()->getLocale();

        $articlesGroup = $builder->create('articles', GroupType::class, [
            'label' => t('Produkt v článcích blogu'),
        ]);

        $blogArticles = $this->blogArticleFacade->getByProduct($product, $locale);

        if (count($blogArticles) < 1) {
            $articlesGroup->add('noBlogArticles', DisplayOnlyType::class, [
                'data' => t('Produkt není v žádných článcích'),
            ]);
        }

        foreach ($blogArticles as $blogArticle) {
            $articlesGroup->add('blogArticle' . $blogArticle->getId(), DisplayOnlyUrlType::class, [
                'label' => $blogArticle->getName($locale),
                'route' => 'admin_blogarticle_edit',
                'route_params' => [
                    'id' => $blogArticle->getId(),
                ],
                'route_label' => $blogArticle->getName($locale),
            ]);
        }

        return $articlesGroup;
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductData::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType(): string
    {
        return ProductFormType::class;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     */
    private function createMainVariantGroup(FormBuilderInterface $builder): void
    {
        $builderMainVariantGroup = $builder->create('builderMainVariantGroup', GroupType::class, [
            'label' => t('Propojené produkty'),
            'position' => ['after' => 'name'],
        ]);

        $allParameters = $this->parameterFacade->getAll();

        $builderMainVariantGroup
            ->add('distinguishingParameterForMainVariantGroup', ChoiceType::class, [
                'required' => false,
                'label' => t('Hlavní rozlišující parametr'),
                'choices' => $allParameters,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'placeholder' => t('Zvolte parametr'),
            ])
            ->add(
                $builder
                    ->create('productsInGroup', ProductsType::class, [
                        'required' => false,
                        'label' => t('Produkty'),
                        'allow_main_variants' => true,
                        'allow_variants' => false,
                        'is_main_variant_group' => true,
                    ])
                    ->addModelTransformer(new RemoveDuplicatesFromArrayTransformer())
            );

        $builder->add($builderMainVariantGroup);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     */
    private function addActionPriceToPricesGroup(FormBuilderInterface $builder): void
    {
        $builderPricesGroup = $builder->get('pricesGroup');
        $actionPriceOptionsByDomainId = [];

        foreach ($this->domain->getAll() as $domainConfig) {
            $domainId = $domainConfig->getId();

            $actionPriceOptionsByDomainId[$domainId] = [
               'currency' => $this->priceExtension->getCurrencyCodeByDomainId($domainId),
            ];
        }

        $builderPricesGroup->add('actionPrices', MultidomainType::class, [
            'entry_type' => MoneyType::class,
            'required' => false,
            'block_name' => 'custom_name',
            'label' => t('Akční cena'),
            'position' => ['after' => 'vat'],
            'options_by_domain_id' => $actionPriceOptionsByDomainId,
            'entry_options' => [
                'scale' => 6,
                'invalid_message' => 'Please enter price in correct format (positive number with decimal separator)',
                'constraints' => [
                    new NotNegativeMoneyAmount(['message' => 'Price must be greater or equal to zero']),
                ],
            ],
        ]);
    }
}
