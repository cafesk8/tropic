<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Component\Domain\DomainHelper;
use App\Component\FlashMessage\FlashMessageSender;
use App\Component\Form\FormBuilderHelper;
use App\Component\GoogleApi\GoogleClient;
use App\Form\ProductSetItemsListType;
use App\Form\ProductsListType;
use App\Model\Blog\Article\BlogArticleFacade;
use App\Model\Pricing\Currency\Currency;
use App\Model\Pricing\Currency\CurrencyFacade;
use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Product\Product;
use App\Model\Product\ProductData;
use App\Model\Product\ProductVariantTropicFacade;
use App\Twig\DateTimeFormatterExtension;
use App\Twig\ProductExtension;
use Google_Service_Exception;
use Shopsys\FormTypesBundle\MultidomainType;
use Shopsys\FormTypesBundle\YesNoType;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Form\Admin\Product\ProductFormType;
use Shopsys\FrameworkBundle\Form\Constraints\NotNegativeMoneyAmount;
use Shopsys\FrameworkBundle\Form\DisplayOnlyType;
use Shopsys\FrameworkBundle\Form\DisplayOnlyUrlType;
use Shopsys\FrameworkBundle\Form\DomainsType;
use Shopsys\FrameworkBundle\Form\FileUploadType;
use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Form\ImageUploadType;
use Shopsys\FrameworkBundle\Form\WarningMessageType;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ProductFormTypeExtension extends AbstractTypeExtension
{
    public const DISABLED_FIELDS = [
        'name',
        'basicInformationGroup.catnum',
        'basicInformationGroup.pohodaId',
        'displayAvailabilityGroup.usingStock',
        'discountExclusionGroup.registrationDiscountDisabled',
        'discountExclusionGroup.promoDiscountDisabled',
        'pricesGroup.productCalculatedPricesGroup.vatsIndexedByDomainId',
        'basicInformationGroup.variantId',
        'displayAvailabilityGroup.categoriesByDomainId',
        'storeStock',
        'accessories',
        'displayAvailabilityGroup.deliveryDays',
        'videosGroup.youtubeVideoIds',
        'basicInformationGroup.brand',
        'displayAvailabilityGroup.unit',
        'basicInformationGroup.ean',
        'basicInformationGroup.warranty',
        'amountGroup.minimumAmount',
        'amountGroup.amountMultiplier',
        'imageGroup.images',
        'displayAvailabilityGroup.shown',
        'parametersGroup.parameters',
        'basicInformationGroup.supplierSet',
        'displayAvailabilityGroup.orderingPriority',
        'displayAvailabilityGroup.foreignSupplier',
        'basicInformationGroup.weight',
        'displayAvailabilityGroup.sellingDenied',
        'basicInformationGroup.bulky',
        'basicInformationGroup.oversized',
        'descriptionsGroup.descriptions.1',
        'shortDescriptionsGroup.shortDescriptions.1',
    ];

    /**
     * @var \App\Model\Product\ProductVariantTropicFacade
     */
    protected $productVariantTropicFacade;

    /**
     * @var \App\Component\FlashMessage\FlashMessageSender
     */
    private $flashMessageSender;

    /**
     * @var \App\Model\Blog\Article\BlogArticleFacade
     */
    private $blogArticleFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \App\Twig\ProductExtension
     */
    private $productExtension;

    /**
     * @var \App\Component\GoogleApi\GoogleClient
     */
    private $googleClient;

    /**
     * @var \App\Twig\DateTimeFormatterExtension
     */
    private $dateTimeFormatterExtension;

    /**
     * @var \App\Component\Form\FormBuilderHelper
     */
    private $formBuilderHelper;

    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @var string[]
     */
    private $dynamicallyDisabledFields;

    /**
     * ProductFormTypeExtension constructor.
     *
     * @param \App\Model\Blog\Article\BlogArticleFacade $blogArticleFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Twig\ProductExtension $productExtension
     * @param \App\Component\GoogleApi\GoogleClient $googleClient
     * @param \App\Twig\DateTimeFormatterExtension $dateTimeFormatterExtension
     * @param \App\Component\Form\FormBuilderHelper $formBuilderHelper
     * @param \App\Model\Product\ProductVariantTropicFacade $productVariantTropicFacade
     * @param \App\Component\FlashMessage\FlashMessageSender $flashMessageSender
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     */
    public function __construct(
        BlogArticleFacade $blogArticleFacade,
        AdminDomainTabsFacade $adminDomainTabsFacade,
        PricingGroupFacade $pricingGroupFacade,
        ProductExtension $productExtension,
        GoogleClient $googleClient,
        DateTimeFormatterExtension $dateTimeFormatterExtension,
        FormBuilderHelper $formBuilderHelper,
        ProductVariantTropicFacade $productVariantTropicFacade,
        FlashMessageSender $flashMessageSender,
        CurrencyFacade $currencyFacade
    ) {
        $this->blogArticleFacade = $blogArticleFacade;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->dateTimeFormatterExtension = $dateTimeFormatterExtension;
        $this->productExtension = $productExtension;
        $this->googleClient = $googleClient;
        $this->formBuilderHelper = $formBuilderHelper;
        $this->productVariantTropicFacade = $productVariantTropicFacade;
        $this->flashMessageSender = $flashMessageSender;
        $this->currencyFacade = $currencyFacade;
        $this->dynamicallyDisabledFields = [];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $product = $options['product'];
        /* @var $product \App\Model\Product\Product|null */

        if ($product !== null && $product->isMainVariant()) {
            $variantGroup = $builder->get('variantGroup');
            $variantGroup->add('variants', ProductsListType::class, [
                'label' => t('Varianty'),
                'top_info_title' => t('Produkt je hlavn?? variantou.'),
            ]);
        }

        $builderBasicInformationGroup = $builder->get('basicInformationGroup');
        $builderBasicInformationGroup
            ->add('variantId', TextType::class, [
                'required' => false,
                'label' => t('ID modifikace'),
                'constraints' => [
                    new Callback([$this, 'validateVariantId']),
                ],
                'position' => ['before' => 'brand'],
            ])
            ->add('warranty', IntegerType::class, [
                'required' => false,
                'label' => t('Z??ru??n?? doba'),
            ])
            ->add('weight', NumberType::class, [
                'required' => false,
                'label' => t('Hmotnost'),
            ])
            ->add('updatedByPohodaAt', DisplayOnlyType::class, [
                'data' => $product !== null ? $this->dateTimeFormatterExtension->formatDateTime($product->getUpdatedByPohodaAt()) : '-',
                'label' => t('Posledn?? aktualizace z IS'),
            ])
            ->add('pohodaId', DisplayOnlyType::class, [
                'data' => $product !== null && $product->getPohodaId() !== null ? $product->getPohodaId() : '-',
                'label' => t('Pohoda ID'),
            ]);

        if ($product !== null) {
            if ($product->isPohodaProductTypeSet()) {
                $pohodaProductType = t('V??robek');
            } elseif ($product->isPohodaProductTypeSingle()) {
                $pohodaProductType = t('Karta');
            } else {
                $pohodaProductType = '-';
            }
            $builderBasicInformationGroup
                ->add('pohodaProductType', DisplayOnlyType::class, [
                    'data' => $pohodaProductType,
                    'label' => t('Typ produktu z Pohody'),
                ]);
        }
        $builderBasicInformationGroup
            ->add('supplierSet', YesNoType::class, [
                'label' => t('V??robek od dodavatele'),
            ]);

        $productFlagsGroup = $builder->create('productFlagsGroup', GroupType::class, [
            'label' => t('P????znaky'),
            'position' => ['after' => 'basicInformationGroup'],
        ]);
        $productFlagsGroup->add('flags', ProductFlagType::class);
        $builder->add($productFlagsGroup);

        $builderBasicInformationGroup->remove('flags');

        $builderStoreStockGroup = $builder->create('storeStock', GroupType::class, [
            'disabled' => $product !== null && $product->isPohodaProductTypeSet(),
            'label' => t('Skladov?? z??soby'),
            'position' => ['after' => 'displayAvailabilityGroup'],
        ]);

        $builderStoreStockGroup->add('stockQuantityByStoreId', StoreStockType::class);

        $builder->add($builderStoreStockGroup);

        $mergadoFeedGroup = $builder->create('generateToMergadoXmlFeeds', GroupType::class, [
            'label' => t('Mergado feed'),
            'position' => ['after' => 'seoGroup'],
        ]);

        $mergadoFeedGroup->add('generateToMergadoXmlFeeds', MultidomainType::class, [
            'label' => t('Generovat tento produkt do Mergado XML feedu'),
            'entry_type' => YesNoType::class,
            'required' => false,
         ]);
        $mergadoFeedGroup->add('namesForMergadoFeed', MultidomainType::class, [
            'label' => t('N??zev pro feed'),
            'entry_type' => TextType::class,
            'required' => false,
        ]);

        $builder->add($mergadoFeedGroup);

        if ($product !== null) {
            $builder->add($this->getArticlesGroup($builder, $product));
        }

        $this->addFileGroup($builder, $options);
        $this->addVideoGroup($builder);
        $this->addStickersGroup($builder, $options);
        $this->addDiscountExclusionGroup($builder, $product);

        if ($product === null || ($product !== null && $product->isMainVariant() === false)) {
            $builder->add($this->getPricesGroup($builder, $product));
        }

        if ($product !== null && $product->isVariant()) {
            $this->extendVariantGroup($builder->get('variantGroup'), $product);
            $builder->get('name')->setDisabled(true);
        }

        $this->extendCatnum($builder->get('basicInformationGroup'));

        $builder->get('basicInformationGroup')
            ->add('giftCertificate', YesNoType::class, [
                'required' => false,
                'label' => t('D??rkov?? poukaz'),
            ])
            ->add('bulky', YesNoType::class, [
                'required' => false,
                'label' => t('Objemn??'),
            ])
            ->add('oversized', YesNoType::class, [
                'required' => false,
                'label' => t('Nadrozm??rn??'),
            ])
            ->add('transportFee', MultidomainType::class, [
                'required' => false,
                'label' => t('Poplatek k doprav??'),
                'entry_type' => MoneyType::class,
            ])
            ->add('transportFeeMultiplier', IntegerType::class, [
                'required' => false,
                'label' => t('Po??et kus??, po kter??m se poplatek nav??????'),
            ]);

        $this->extendOutOfStockAction($builder->get('displayAvailabilityGroup')->get('stockGroup'), $product);
        $this->extendAccessoriesGroup($builder);
        $this->extendDisplayAvailabilityGroup($builder->get('displayAvailabilityGroup'), $product);
        $this->addAmountGroup($builder, $product);
        $this->addProductSetItemsGroup($builder, $product);
        $this->extendDescriptionGroups($builder, $product);

        $this->formBuilderHelper->disableFieldsByConfigurations($builder, $this->getDisabledFields());
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Product\Product $product
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    public function getArticlesGroup(FormBuilderInterface $builder, Product $product): FormBuilderInterface
    {
        $locale = $this->adminDomainTabsFacade->getSelectedDomainConfig()->getLocale();

        $articlesGroup = $builder->create('articles', GroupType::class, [
            'label' => t('Produkt v ??l??nc??ch blogu'),
        ]);

        $blogArticles = $this->blogArticleFacade->getByProduct($product, $locale);

        if (count($blogArticles) < 1) {
            $articlesGroup->add('noBlogArticles', DisplayOnlyType::class, [
                'data' => t('Produkt nen?? v ????dn??ch ??l??nc??ch'),
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
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Product\Product $product
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    public function addProductSetItemsGroup(FormBuilderInterface $builder, ?Product $product): FormBuilderInterface
    {
        $setItemGroup = $builder->create('setItemsGroup', GroupType::class, [
            'label' => t('Polo??ky setu'),
            'position' => ['after' => 'accessories'],
        ]);

        if ($product !== null) {
            $setItemGroup->add('setItems', ProductSetItemsListType::class, [
                'label' => t('Polo??ky setu'),
                'required' => false,
                'main_product' => $product,
                'top_info_title' => !$product->isPohodaProductTypeSet() ? t('Produkt nen?? v Pohod?? typu "V??robek"') : '',
            ]);
        }

        return $builder->add($setItemGroup);
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
    public static function getExtendedTypes(): iterable
    {
        yield ProductFormType::class;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Product\Product|null $product
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function getPricesGroup(FormBuilderInterface $builder, ?Product $product): FormBuilderInterface
    {
        $pricesGroupBuilder = $builder->get('pricesGroup');
        $productCalculatedPricesGroup = $pricesGroupBuilder->get('productCalculatedPricesGroup');
        $productCalculatedPricesGroup->remove('manualInputPricesByPricingGroupId');

        $manualInputPricesByPricingGroup = $builder->create('manualInputPricesByPricingGroupId', FormType::class, [
            'compound' => true,
            'render_form_row' => false,
            'disabled' => $product !== null && $product->isMainVariant(),
        ]);

        /** @var \App\Model\Pricing\Group\PricingGroup $pricingGroup */
        foreach ($this->pricingGroupFacade->getAllOrderedByInternalId() as $pricingGroup) {
            $manualInputPricesByPricingGroup->add($pricingGroup->getId(), MoneyType::class, [
                'scale' => 6,
                'required' => false,
                'disabled' => $pricingGroup->getInternalId() !== null
                    && (
                        $pricingGroup->isCalculatedFromDefault()
                        || (
                            $product !== null
                            && $this->currencyFacade->getDomainDefaultCurrencyByDomainId($pricingGroup->getDomainId())->getCode() === Currency::CODE_EUR
                        )
                    ),
                'invalid_message' => 'Please enter price in correct format (positive number with decimal separator)',
                'constraints' => [
                    new NotNegativeMoneyAmount(['message' => 'Price must be greater or equal to zero']),
                ],
                'label' => $pricingGroup->getName(),
            ]);

            if ($pricingGroup->getDomainId() === DomainHelper::CZECH_DOMAIN) {
                $this->dynamicallyDisabledFields[] = 'pricesGroup.productCalculatedPricesGroup.manualInputPricesByPricingGroupId.' . $pricingGroup->getId();
            }
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
     * @param \App\Model\Product\Product|null $product
     */
    private function extendOutOfStockAction(FormBuilderInterface $builder, ?Product $product): void
    {
        if ($product !== null) {
            $codeFieldOptions = $builder->get('outOfStockAction')->getOptions();
            $codeFieldType = get_class($builder->get('outOfStockAction')->getType()->getInnerType());

            if ($product->isMainVariant()) {
                $codeFieldOptions['constraints'] = null;
            }

            if ($product->isPohodaProductTypeSet()) {
                $codeFieldType = DisplayOnlyType::class;
                $codeFieldOptions = [
                    'data' => t('Exclude from sale'),
                    'label' => $codeFieldOptions['label'],
                ];
            }

            $builder->add('outOfStockAction', $codeFieldType, $codeFieldOptions);
        }
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     */
    private function extendAccessoriesGroup(FormBuilderInterface $builder): void
    {
        $builder->add('accessories', ProductsListType::class, [
            'label' => t('Souvisej??c?? zbo????'),
        ]);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $displayAvailabilityGroup
     * @param \App\Model\Product\Product|null $product
     */
    private function extendDisplayAvailabilityGroup(
        FormBuilderInterface $displayAvailabilityGroup,
        ?Product $product
    ): void {
        $displayAvailabilityGroup->remove('hidden');

        if ($product !== null && $product->isUsingStock() && $product->getCalculatedSellingDenied()
            && $product->getStockQuantity() <= 0
        ) {
            $displayAvailabilityGroup->remove('productCalculatedSellingDeniedInfo');
            $displayAvailabilityGroup
                ->add('productCalculatedSellingDeniedInfo', WarningMessageType::class, [
                    'position' => ['after' => 'sellingTo'],
                    'data' => t('Zbo???? je ozna??eno jako vyprodan?? z d??vodu nulov??ch skladov??ch z??sob.'),
                ]);
        }

        if ($product !== null && $product->isMainVariant()) {
            $displayAvailabilityGroup->remove('productCalculatedHiddenInfo');
        }

        $displayAvailabilityGroup
            ->remove('hidden')
            ->remove('sellingDenied')
            ->add('shown', DomainsType::class, [
                'required' => false,
                'label' => t('Display on'),
                'position' => ['before' => 'sellingDenied'],
                'attr' => [
                    'labelIcon' => true,
                    'labelIconTitle' => t('Skryt?? zbo???? se nezobrazuje ve v??pisech ani nelze vyhledat. Detail tohoto zbo???? nen?? dostupn?? p????m??m p????stupem z URL. Zbo???? nelze vlo??it do ko????ku.'),
                ],
            ])
            ->add('sellingDenied', YesNoType::class, [
                'required' => false,
                'label' => t('Exclude from sale'),
                'attr' => [
                    'icon' => true,
                    'iconTitle' => t('Produkty takto vy??azen?? z prodeje se nikde na e-shopu nezobrazuj?? (a to ani v p????pad??, ??e maj?? zobrazov??n?? v????e nastaveno pomoc?? "Zobrazit na").'),
                ],
            ])
            ->add('mallExport', YesNoType::class, [
                'required' => false,
                'label' => t('Export do Mall.cz'),
            ])
            ->add('mallExportedAt', DisplayOnlyType::class, [
                'label' => t('Exportov??no do Mall.cz'),
                'data' => $product !== null ? $this->dateTimeFormatterExtension->formatDateTime($product->getMallExportedAt()) : '~',
            ])
            ->add('deliveryDays', TextType::class, [
                'required' => false,
                'label' => t('Dod??n??'),
            ])->add('foreignSupplier', YesNoType::class, [
                'required' => false,
                'label' => t('Zahrani??n?? dodavatel'),
                'position' => ['before' => 'usingStock'],
            ]);
        $usingStockItem = $displayAvailabilityGroup->get('usingStock');
        $usingStockItem->setDisabled(true);

        $stockGroup = $displayAvailabilityGroup->get('stockGroup');
        $stockGroup->remove('outOfStockAction');
        $stockGroup->remove('outOfStockAvailability');

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
     * @param \App\Model\Product\Product $product
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
        $videosGroup = $builder->create('videosGroup', GroupType::class, [
            'label' => t('Videa'),
            'position' => ['after' => 'imageGroup'],
            'required' => false,
        ]);

        $videosGroup
            ->add($builder->create('youtubeVideoIds', YoutubeVideosType::class, [
                'entry_type' => TextType::class,
                'label' => t('YouTube videa'),
                'entry_options' => [
                    'required' => false,
                ],
            ]));

        $builder->add($videosGroup);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Product\Product|null $product
     */
    private function addAmountGroup(FormBuilderInterface $builder, ?Product $product)
    {
        $amountGroup = $builder->create('amountGroup', GroupType::class, [
            'label' => t('Minimum quantity and multiples'),
            'position' => ['after' => 'storeStock'],
        ]);

        if ($product !== null && $product->isVariant()) {
            $amountGroup->add('minimumAmount', DisplayOnlyType::class, [
                'mapped' => false,
                'required' => false,
                'data' => t('Minim??ln?? mno??stv?? m????ete nastavit na kart?? hlavn?? varianty.'),
                'attr' => [
                    'class' => 'form-input-disabled form-line--disabled position__actual',
                ],
            ]);

            $amountGroup->add('amountMultiplier', DisplayOnlyType::class, [
                'mapped' => false,
                'required' => false,
                'data' => t('N??sobky pro n??kup m????ete nastavit na kart?? hlavn?? varianty.'),
                'attr' => [
                    'class' => 'form-input-disabled form-line--disabled position__actual',
                ],
            ]);
        } else {
            $amountGroup->add('minimumAmount', IntegerType::class, [
                'constraints' => [
                    new Constraints\GreaterThan(0),
                    new Constraints\NotBlank(),
                ],
                'label' => t('Minimum amount'),
            ]);

            $amountGroup->add('amountMultiplier', IntegerType::class, [
                'constraints' => [
                    new Constraints\GreaterThan(0),
                    new Constraints\NotBlank(),
                ],
                'label' => t('Amount multiples'),
            ]);
        }

        $builder->add($amountGroup);
    }

    /**
     * @param string|null $youtubeVideoId
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public function validateYoutubeVideo(?string $youtubeVideoId, ExecutionContextInterface $context): void
    {
        if ($youtubeVideoId === null) {
            $context->addViolation('Vlo??en?? ID Youtube videa neobsahuje ????dn?? video.');
            return;
        }

        try {
            $youtubeResponse = $this->googleClient->getVideoList($youtubeVideoId);
            if ($youtubeResponse->getPageInfo()->getTotalResults() === 0) {
                $context->addViolation('Vlo??en?? ID Youtube videa neobsahuje ????dn?? video.');
            }
        } catch (Google_Service_Exception $googleServiceException) {
            $this->flashMessageSender->addInfoFlash(t('Nepovedlo se p??ipojit ke Google API, tak??e se nezkontrolovala platnost ID Youtube videa.'));
            $this->flashMessageSender->addInfoFlash(t('Pokud ID Youtube videa nen?? platn??, tak se video nebude zobrazovat na frontendu.'));
            $this->flashMessageSender->addInfoFlash(t('ID Youtube videa bylo p??esto k produktu ulo??eno.'));
        }
    }

    /**
     * @param string|null $variantId
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public function validateVariantId(?string $variantId, ExecutionContextInterface $context): void
    {
        /** @var \Symfony\Component\Form\Form $form */
        $form = $context->getRoot();
        /** @var \App\Model\Product\Product|null $product */
        $product = $form->getConfig()->getOption('product');
        if ($this->productVariantTropicFacade->isVariant($variantId)) {
            $mainVariantVariantId = Product::getMainVariantVariantIdFromVariantVariantId($variantId);
            $variantNumber = Product::getVariantNumber($variantId);
            if (strlen($mainVariantVariantId) === 0
                || strlen($variantNumber) === 0
                || !preg_match('#^\d+$#', $variantNumber)
            ) {
                $context->addViolation(
                    'Zadan?? ID modifikace m?? neplatn?? form??t (o??ek??v?? se nenulov?? po??et znak?? p??ed i za hv??zdi??kou, p??i??em?? ????st za hv??zdi??kou by m??la obsahovat jen ????slice)'
                );
                return;
            }
        }
        if ($variantId !== null) {
            $existingProductByVariantId = $this->productVariantTropicFacade->findByVariantId($variantId);
            if ($existingProductByVariantId !== null && ($product === null || $product !== null && $existingProductByVariantId->getId() !== $product->getId())) {
                $context->addViolation('Zadan?? ID modifikace je ji?? v syst??mu p??i??azeno jin??mu produktu');
            }
        }

        if ($this->productVariantTropicFacade->isVariant($variantId)
            && $this->productVariantTropicFacade->findMainVariantByVariantId($variantId) === null
        ) {
            $context->addViolation('Nen?? mo??n?? vyvo??it variantu, pro kterou neexistuje odpov??daj??c?? hlavn?? varianta');
        }
    }

    /**
     * @return string[]
     */
    private function getDisabledFields(): array
    {
        return array_merge(self::DISABLED_FIELDS, $this->dynamicallyDisabledFields);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Product\Product|null $product
     */
    private function addDiscountExclusionGroup(FormBuilderInterface $builder, ?Product $product): void
    {
        $discountExclusionGroup = $builder->create('discountExclusionGroup', GroupType::class, [
            'label' => t('Vyjmut?? ze slev'),
            'position' => ['before' => 'pricesGroup'],
        ]);

        if ($product instanceof Product && $product->isInAnySaleStock()) {
            $discountExclusionGroup->add('saleExclusionWarning', WarningMessageType::class, [
                'data' => t('Produkt je vy??azen ze slev, proto??e je na v??prodejov??m skladu. ' .
                    'Zobrazen?? hodnoty checkbox?? za??nou platit, a?? bude produkt z v??prodejov??ch sklad?? vyprod??n.'),
            ]);
        }

        $discountExclusionGroup->add('registrationDiscountDisabled', MultidomainType::class, [
            'entry_type' => YesNoType::class,
            'label' => t('Vyjmout ze slev za registraci'),
            'required' => false,
        ]);

        $discountExclusionGroup->add('promoDiscountDisabled', MultidomainType::class, [
            'entry_type' => YesNoType::class,
            'label' => t('Vyjmout ze slev za slevov?? kup??ny'),
            'required' => false,
        ]);

        $builder->add($discountExclusionGroup);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Product\Product|null $product
     */
    private function extendDescriptionGroups(FormBuilderInterface $builder, ?Product $product): void
    {
        $descriptionsGroup = $builder->get('descriptionsGroup');
        $shortDescriptionsGroup = $builder->get('shortDescriptionsGroup');

        if ($product === null || !$product->isVariant()) {
            $descriptionsGroup->add('descriptionAutomaticallyTranslated', YesNoType::class, [
                'label' => 'P??ekl??dat p??es Google Translate',
                'position' => 'first',
                'required' => false,
            ]);
            $shortDescriptionsGroup->add('shortDescriptionAutomaticallyTranslated', YesNoType::class, [
                'label' => 'P??ekl??dat p??es Google Translate',
                'position' => 'first',
                'required' => false,
            ]);
        }
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    private function addFileGroup(FormBuilderInterface $builder, array $options): void
    {
        $filesGroup = $builder->create('fileGroup', GroupType::class, [
            'label' => t('Soubory'),
            'position' => ['after' => 'stickersGroup'],
            'required' => false,
        ]);

        $filesGroup
            ->add('files', FileUploadType::class, [
                'required' => false,
                'file_entity_class' => Product::class,
                'file_constraints' => [
                    new Constraints\File([
                        'mimeTypes' => $this->getAllowedFileMimeTypes(),
                        'mimeTypesMessage' => 'Soubor m????e b??t pouze ve form??tu PNG, JPG, GIF, BMP, TXT, RTF, PDF, DOC, DOCX, ODF, XLS, XLSX, PPT, PPTX, XML, HTM, HTML, CSV, ZIP',
                        'maxSize' => '45M',
                        'maxSizeMessage' => 'Velikost souboru je p????li?? velk??: ({{ size }} {{ suffix }}). '
                            . 'Maxim??ln?? velikost souboru je {{ limit }} {{ suffix }}.',
                    ]),
                ],
                'info_text' => t('M????ete nahr??t soubor v t??chto form??tech: PNG, JPG, GIF, BMP, TXT, RTF, PDF, DOC, DOCX, ODF, XLS, XLSX, PPT, PPTX, XML, HTM, HTML, CSV, ZIP'),
                'entity' => $options['product'],
                'label' => t('Files'),
            ]);

        $builder->add($filesGroup);
    }

    /**
     * @return string[]
     */
    private function getAllowedFileMimeTypes(): array
    {
        return [
            'application/msword',
            'application/pdf',
            'application/rtf',
            'application/vnd.ms-excel',
            'application/vnd.ms-powerpoint',
            'application/vnd.oasis.opendocument.chart',
            'application/vnd.oasis.opendocument.formula',
            /* There is a bug in PHP https://bugs.php.net/bug.php?id=77784 that causes duplicated mime-type for XLSX, DOCX and similar file types
               so this is a temporary fix until the bug is fixed */
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.openxmlformats-officedocument.presentationml.presentationapplication/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheetapplication/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.documentapplication/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/xml',
            'application/zip',
            'image/bmp',
            'image/gif',
            'image/jpeg',
            'image/jpg',
            'image/png',
            'text/csv',
            'text/html',
            'text/plain',
            'text/xml',
        ];
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    private function addStickersGroup(FormBuilderInterface $builder, array $options): void
    {
        $builderStickersGroup = $builder->create('stickersGroup', GroupType::class, [
            'label' => t('Grafick?? n??lepky'),
            'position' => ['after' => 'videosGroup'],
        ]);

        $builderStickersGroup
            ->add('stickers', ImageUploadType::class, [
                'required' => false,
                'image_entity_class' => BaseProduct::class,
                'file_constraints' => [
                    new Constraints\Image([
                        'mimeTypes' => ['image/png', 'image/jpg', 'image/jpeg', 'image/gif'],
                        'mimeTypesMessage' => 'Image can be only in JPG, GIF or PNG format',
                        'maxSize' => '2M',
                        'maxSizeMessage' => 'Uploaded image is to large ({{ size }} {{ suffix }}). '
                            . 'Maximum size of an image is {{ limit }} {{ suffix }}.',
                    ]),
                ],
                'entity' => $options['product'],
                'info_text' => t('You can upload following formats: PNG, JPG, GIF'),
                'label' => t('Grafick?? n??lepky'),
                'image_type' => Product::IMAGE_TYPE_STICKER,
            ])
            ->add('stickerDimensionsInfo', DisplayOnlyType::class, [
                'label' => t('Doporu??en?? rozm??ry'),
                'data' => t('??????ka: 100px, v????ka: 100px'),
            ]);

        $builder->add($builderStickersGroup);
    }

    /**
     * @return string
     */
    public function getExtendedType(): string
    {
        return ProductFormType::class;
    }
}
