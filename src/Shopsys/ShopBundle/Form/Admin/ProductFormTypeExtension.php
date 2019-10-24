<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FormTypesBundle\MultidomainType;
use Shopsys\FormTypesBundle\YesNoType;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Form\Admin\Product\ProductFormType;
use Shopsys\FrameworkBundle\Form\Constraints\NotNegativeMoneyAmount;
use Shopsys\FrameworkBundle\Form\DisplayOnlyType;
use Shopsys\FrameworkBundle\Form\DisplayOnlyUrlType;
use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Form\ProductsType;
use Shopsys\FrameworkBundle\Form\ProductType;
use Shopsys\FrameworkBundle\Form\Transformers\RemoveDuplicatesFromArrayTransformer;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFacade;
use Shopsys\FrameworkBundle\Twig\PriceExtension;
use Shopsys\ShopBundle\Component\GoogleApi\GoogleClient;
use Shopsys\ShopBundle\Form\Transformers\RemoveProductTransformer;
use Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade;
use Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade;
use Shopsys\ShopBundle\Model\Product\Flag\FlagFacade;
use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductData;
use Shopsys\ShopBundle\Twig\DateTimeFormatterExtension;
use Shopsys\ShopBundle\Twig\ProductExtension;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
     * @var \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \Shopsys\ShopBundle\Twig\ProductExtension
     */
    private $productExtension;

    /**
     * @var \Shopsys\ShopBundle\Component\GoogleApi\GoogleClient
     */
    private $googleClient;

    /**
     * @var \Shopsys\ShopBundle\Twig\DateTimeFormatterExtension
     */
    private $dateTimeFormatterExtension;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Flag\FlagFacade
     */
    private $flagFacade;

    /**
     * ProductFormTypeExtension constructor.
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterFacade $parameterFacade
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleFacade $blogArticleFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \Shopsys\FrameworkBundle\Twig\PriceExtension $priceExtension
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \Shopsys\ShopBundle\Twig\ProductExtension $productExtension
     * @param \Shopsys\ShopBundle\Component\GoogleApi\GoogleClient $googleClient
     * @param \Shopsys\ShopBundle\Twig\DateTimeFormatterExtension $dateTimeFormatterExtension
     * @param \Shopsys\ShopBundle\Model\Product\Flag\FlagFacade $flagFacade
     */
    public function __construct(
        ParameterFacade $parameterFacade,
        BlogArticleFacade $blogArticleFacade,
        AdminDomainTabsFacade $adminDomainTabsFacade,
        PriceExtension $priceExtension,
        Domain $domain,
        PricingGroupFacade $pricingGroupFacade,
        ProductExtension $productExtension,
        GoogleClient $googleClient,
        DateTimeFormatterExtension $dateTimeFormatterExtension,
        FlagFacade $flagFacade
    ) {
        $this->parameterFacade = $parameterFacade;
        $this->blogArticleFacade = $blogArticleFacade;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
        $this->priceExtension = $priceExtension;
        $this->domain = $domain;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->dateTimeFormatterExtension = $dateTimeFormatterExtension;
        $this->productExtension = $productExtension;
        $this->googleClient = $googleClient;
        $this->flagFacade = $flagFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $product = $options['product'];
        /* @var $product \Shopsys\FrameworkBundle\Model\Product\Product|null */

        $builderBasicInformationGroup = $builder->get('basicInformationGroup');
        $builderBasicInformationGroup
            ->add('finished', YesNoType::class, [
                'required' => false,
                'label' => t('Produkt je hotový'),
            ]);
        $defaultFlagForFreeTransportAndPayment = $this->flagFacade->getDefaultFlagForFreeTransportAndPayment();
        $builderBasicInformationGroup->add('flags', ChoiceType::class, [
            'choices' => $this->flagFacade->getAll(),
            'choice_label' => 'name',
            'choice_value' => 'id',
            'multiple' => true,
            'expanded' => true,
            'label' => t('Flags'),
            'choice_attr' => function ($flag) use ($defaultFlagForFreeTransportAndPayment) {
                if ($flag === $defaultFlagForFreeTransportAndPayment) {
                    return [
                        'disabled' => true,
                    ];
                }

                return [];
            },
        ]);
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

        $this->addVideoGroup($builder);

        if ($product !== null && $product->getMainVariantGroup() !== null) {
            $this->createMainVariantGroup($builder, $product);
            $this->addGiftGroup($builder);
        } else {
            if ($product !== null && $product->isMainVariant() === false) {
                $this->addActionPriceToPricesGroup($builder);
                $builder->add($this->getPricesGroup($builder, $product));
            }
        }

        if ($product !== null && $product->isNoneVariant() === true) {
            $this->addGiftGroup($builder);
        }

        if ($product !== null && $product->isVariant()) {
            $this->extendVariantGroup($builder->get('variantGroup'), $product);
            $builder->get('name')->setDisabled(true);
        }

        $this->extendCatnum($builder->get('basicInformationGroup'));

        $builder->get('basicInformationGroup')
            ->add('generateToHsSportXmlFeed', YesNoType::class, [
                'required' => false,
                'label' => t('Generovat tento produkt do HS-SPORT XML feedu'),
            ]);

        $this->extendOutOfStockAction($builder->get('displayAvailabilityGroup')->get('stockGroup'), $product);
        $this->extendAccessoriesGroup($builder);
        $this->extendDisplayAvailabilityGroup($builder->get('displayAvailabilityGroup'), $product);
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
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     */
    private function createMainVariantGroup(FormBuilderInterface $builder, Product $product): void
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
                    ->addModelTransformer(new RemoveProductTransformer($product))
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

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \Shopsys\ShopBundle\Model\Product\Product|null $product
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function getPricesGroup(FormBuilderInterface $builder, ?Product $product): FormBuilderInterface
    {
        $pricesGroupBuilder = $builder->get('pricesGroup');
        $pricesGroupBuilder->setDisabled(true);

        $productCalculatedPricesGroup = $pricesGroupBuilder->get('productCalculatedPricesGroup');
        $productCalculatedPricesGroup->remove('manualInputPricesByPricingGroupId');

        $manualInputPricesByPricingGroup = $builder->create('manualInputPricesByPricingGroupId', FormType::class, [
            'compound' => true,
            'render_form_row' => false,
            'disabled' => $product !== null && $product->isMainVariant(),
        ]);

        /** @var \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup */
        foreach ($this->pricingGroupFacade->getAll() as $pricingGroup) {
            $manualInputPricesByPricingGroup->add($pricingGroup->getId(), MoneyType::class, [
                'scale' => 6,
                'required' => false,
                'disabled' => $pricingGroup->getInternalId() !== null && $pricingGroup->getInternalId() !== PricingGroup::PRICING_GROUP_ORDINARY_CUSTOMER,
                'invalid_message' => 'Please enter price in correct format (positive number with decimal separator)',
                'constraints' => [
                    new NotNegativeMoneyAmount(['message' => 'Price must be greater or equal to zero']),
                ],
                'label' => $pricingGroup->getName(),
            ]);
        }

        $productCalculatedPricesGroup->add($manualInputPricesByPricingGroup);
        $pricesGroupBuilder->add($productCalculatedPricesGroup);

        return $pricesGroupBuilder;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $basicInformationGroup
     */
    private function extendCatnum(FormBuilderInterface $basicInformationGroup): void
    {
        $catnumFieldOptions = $basicInformationGroup->get('catnum')->getOptions();
        $catnumFieldOptions['label'] = t('SKU');
        $catnumFieldType = get_class($basicInformationGroup->get('catnum')->getType()->getInnerType());
        $basicInformationGroup->add('catnum', $catnumFieldType, $catnumFieldOptions);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \Shopsys\ShopBundle\Model\Product\Product|null $product
     */
    private function extendOutOfStockAction(FormBuilderInterface $builder, ?Product $product): void
    {
        if ($product !== null && $product->isMainVariant()) {
            $codeFieldOptions = $builder->get('outOfStockAction')->getOptions();
            $codeFieldOptions['constraints'] = null;
            $codeFieldType = get_class($builder->get('outOfStockAction')->getType()->getInnerType());
            $builder->add('outOfStockAction', $codeFieldType, $codeFieldOptions);
        }
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     */
    private function extendAccessoriesGroup(FormBuilderInterface $builder): void
    {
        $codeFieldOptions = $builder->get('accessories')->getOptions();
        $codeFieldOptions['label'] = t('Dolaďte svůj outfit');
        $codeFieldType = get_class($builder->get('accessories')->getType()->getInnerType());
        $builder->add('accessories', $codeFieldType, $codeFieldOptions);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     */
    private function addGiftGroup(FormBuilderInterface $builder)
    {
        $giftGroup = $builder->create('giftGroup', GroupType::class, [
            'label' => t('Dárek za korunu'),
        ]);

        $giftGroup->add('gift', ProductType::class, [
            'required' => false,
            'label' => t('Dárek'),
            'allow_main_variants' => true,
            'allow_variants' => true,
            'enableRemove' => true,
        ]);

        $builder->add($giftGroup);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $displayAvailabilityGroup
     * @param \Shopsys\ShopBundle\Model\Product\Product|null $product
     */
    private function extendDisplayAvailabilityGroup(FormBuilderInterface $displayAvailabilityGroup, ?Product $product): void
    {
        $displayAvailabilityGroup
            ->add('mallExport', YesNoType::class, [
                'required' => false,
                'label' => t('Export do Mall.cz'),
            ])
            ->add('mallExportedAt', DisplayOnlyType::class, [
                'label' => t('Exportováno do Mall.cz'),
                'data' => $product !== null ? $this->dateTimeFormatterExtension->formatDateTime($product->getMallExportedAt()) : '~',
            ]);

        $stockGroup = $displayAvailabilityGroup->get('stockGroup');

        $stockQuantity = 0;
        if ($product !== null) {
            if ($product->isMainVariant() === true) {
                $stockQuantity = $product->getTotalStockQuantityOfProductVariants();
            } else {
                $stockQuantity = $product->getStockQuantity();
            }
        }

        $stockGroup->remove('stockQuantity');
        $stockGroup->add('stockQuantity', DisplayOnlyType::class, [
            'label' => t('Skladem'),
            'data' => $stockQuantity,
        ]);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $variantGroup
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     */
    private function extendVariantGroup(FormBuilderInterface $variantGroup, Product $product)
    {
        $mainVariant = $product->getMainVariant();
        $mainVariantUrlOptions = $variantGroup->get('mainVariantUrl')->getOptions();
        $mainVariantUrlOptions['route_label'] = $this->productExtension->getProductDisplayName($mainVariant);
        $mainVariantUrlType = get_class($variantGroup->get('mainVariantUrl')->getType()->getInnerType());
        $variantGroup->add('mainVariantUrl', $mainVariantUrlType, $mainVariantUrlOptions);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     */
    private function addVideoGroup(FormBuilderInterface $builder)
    {
        $videoGroup = $builder->create('videoGroup', GroupType::class, [
            'label' => t('Video'),
            'position' => ['after' => 'imageGroup'],
        ]);

        $videoGroup->add('youtubeVideoId', TextType::class, [
            'required' => false,
            'label' => t('Youtube video id'),
            'constraints' => [
                new Callback([$this, 'validateYoutubeVideo']),
            ],
            'attr' => [
                'class' => 'js-video-id',
            ],
        ]);

        $builder->add($videoGroup);
    }

    /**
     * @param string|null $youtubeVideoId
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public function validateYoutubeVideo(?string $youtubeVideoId, ExecutionContextInterface $context): void
    {
        if ($youtubeVideoId === null) {
            return;
        }

        $youtubeResponse = $this->googleClient->getVideoList($youtubeVideoId);
        if ($youtubeResponse->getPageInfo()->getTotalResults() === 0) {
            $context->addViolation('Vložené youtube id neobsahuje žádné video.');
        }
    }
}
