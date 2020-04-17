<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Component\Form\FormBuilderHelper;
use App\Component\GoogleApi\GoogleClient;
use App\Form\ProductsListType;
use App\Model\Blog\Article\BlogArticleFacade;
use App\Model\Pricing\Group\PricingGroup;
use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Product\Flag\FlagFacade;
use App\Model\Product\Product;
use App\Model\Product\ProductData;
use App\Model\Product\ProductVariantTropicFacade;
use App\Twig\DateTimeFormatterExtension;
use App\Twig\ProductExtension;
use Google_Service_Exception;
use Shopsys\FormTypesBundle\YesNoType;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender;
use Shopsys\FrameworkBundle\Form\Admin\Product\ProductFormType;
use Shopsys\FrameworkBundle\Form\Constraints\NotNegativeMoneyAmount;
use Shopsys\FrameworkBundle\Form\DisplayOnlyType;
use Shopsys\FrameworkBundle\Form\DisplayOnlyUrlType;
use Shopsys\FrameworkBundle\Form\GroupType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
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
        'catnum',
        'pohodaId',
        'shortDescriptions',
        'descriptions',
        'usingStock',
        'registrationDiscountDisabled',
        'manualInputPricesByPricingGroupId',
        'vatsIndexedByDomainId',
        'variantId',
        'categoriesByDomainId',
    ];

    /**
     * @var \App\Model\Product\ProductVariantTropicFacade
     */
    protected $productVariantTropicFacade;

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
     * @var \App\Model\Product\Flag\FlagFacade
     */
    private $flagFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender
     */
    private $flashMessageSender;

    /**
     * @var \App\Component\Form\FormBuilderHelper
     */
    private $formBuilderHelper;

    /**
     * ProductFormTypeExtension constructor.
     *
     * @param \App\Model\Blog\Article\BlogArticleFacade $blogArticleFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Twig\ProductExtension $productExtension
     * @param \App\Component\GoogleApi\GoogleClient $googleClient
     * @param \App\Twig\DateTimeFormatterExtension $dateTimeFormatterExtension
     * @param \App\Model\Product\Flag\FlagFacade $flagFacade
     * @param \Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageSender $flashMessageSender
     * @param \App\Component\Form\FormBuilderHelper $formBuilderHelper
     * @param \App\Model\Product\ProductVariantTropicFacade $productVariantTropicFacade
     */
    public function __construct(
        BlogArticleFacade $blogArticleFacade,
        AdminDomainTabsFacade $adminDomainTabsFacade,
        PricingGroupFacade $pricingGroupFacade,
        ProductExtension $productExtension,
        GoogleClient $googleClient,
        DateTimeFormatterExtension $dateTimeFormatterExtension,
        FlagFacade $flagFacade,
        FlashMessageSender $flashMessageSender,
        FormBuilderHelper $formBuilderHelper,
        ProductVariantTropicFacade $productVariantTropicFacade
    ) {
        $this->blogArticleFacade = $blogArticleFacade;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->dateTimeFormatterExtension = $dateTimeFormatterExtension;
        $this->productExtension = $productExtension;
        $this->googleClient = $googleClient;
        $this->flagFacade = $flagFacade;
        $this->flashMessageSender = $flashMessageSender;
        $this->formBuilderHelper = $formBuilderHelper;
        $this->productVariantTropicFacade = $productVariantTropicFacade;
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
                'top_info_title' => t('Produkt je hlavní variantou.'),
            ]);
        }

        $builderBasicInformationGroup = $builder->get('basicInformationGroup');
        $builderBasicInformationGroup
            ->add('variantId', TextType::class, [
                'required' => false,
                'label' => t('ID modifikace'),
                'position' => ['before' => 'flags'],
                'constraints' => [
                    new Callback([$this, 'validateVariantId']),
                ],
            ])
            ->add('updatedByPohodaAt', DisplayOnlyType::class, [
                'data' => $product !== null ? $this->dateTimeFormatterExtension->formatDateTime($product->getUpdatedByPohodaAt()) : '-',
                'label' => t('Poslední aktualizace z IS'),
            ])
            ->add('pohodaId', DisplayOnlyType::class, [
                'data' => $product !== null && $product->getPohodaId() !== null ? $product->getPohodaId() : '-',
                'label' => t('Pohoda ID'),
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
            'required' => false,
        ]);

        $builderStoreStockGroup = $builder->create('storeStock', GroupType::class, [
            'label' => t('Skladové zásoby'),
            'position' => ['after' => 'displayAvailabilityGroup'],
        ]);

        $builderStoreStockGroup->add('stockQuantityByStoreId', StoreStockType::class);

        $builder->add($builderStoreStockGroup);

        if ($product !== null) {
            $builder->add($this->getArticlesGroup($builder, $product));
        }

        $this->addVideoGroup($builder);

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
                'label' => t('Dárkový poukaz'),
            ]);

        $this->extendOutOfStockAction($builder->get('displayAvailabilityGroup')->get('stockGroup'), $product);
        $this->extendAccessoriesGroup($builder);
        $this->extendDisplayAvailabilityGroup($builder->get('displayAvailabilityGroup'), $product);
        $this->addAmountGroup($builder, $product);
        $this->formBuilderHelper->disableFieldsByConfigurations($builder, self::DISABLED_FIELDS);
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
     * @param \App\Model\Product\Product|null $product
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function getPricesGroup(FormBuilderInterface $builder, ?Product $product): FormBuilderInterface
    {
        $pricesGroupBuilder = $builder->get('pricesGroup');
        $pricesGroupBuilder->add('registrationDiscountDisabled', YesNoType::class, [
            'label' => t('Vyjmout ze slev za registraci'),
            'position' => 'first',
        ]);

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
                'disabled' => $pricingGroup->getInternalId() !== null && $pricingGroup->isCalculatedFromDefault(),
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
     * @param \App\Model\Product\Product|null $product
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
        $codeFieldOptions['label'] = t('Související zboží');
        $codeFieldType = get_class($builder->get('accessories')->getType()->getInnerType());
        $builder->add('accessories', $codeFieldType, $codeFieldOptions);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $displayAvailabilityGroup
     * @param \App\Model\Product\Product|null $product
     */
    private function extendDisplayAvailabilityGroup(
        FormBuilderInterface $displayAvailabilityGroup,
        ?Product $product
    ): void {
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
                'allow_add' => true,
                'allow_delete' => true,
                'entry_options' => [
                    'attr' => [
                        'class' => 'js-video-id',
                    ],
                    'constraints' => [
                        new Callback([$this, 'validateYoutubeVideo']),
                    ],
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
                'data' => t('Minimální množství můžete nastavit na kartě hlavní varianty.'),
                'attr' => [
                    'class' => 'form-input-disabled form-line--disabled position__actual font-size-13',
                ],
            ]);

            $amountGroup->add('amountMultiplier', DisplayOnlyType::class, [
                'mapped' => false,
                'required' => false,
                'data' => t('Násobky pro nákup můžete nastavit na kartě hlavní varianty.'),
                'attr' => [
                    'class' => 'form-input-disabled form-line--disabled position__actual font-size-13',
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
            $context->addViolation('Vložené ID Youtube videa neobsahuje žádné video.');
            return;
        }

        try {
            $youtubeResponse = $this->googleClient->getVideoList($youtubeVideoId);
            if ($youtubeResponse->getPageInfo()->getTotalResults() === 0) {
                $context->addViolation('Vložené ID Youtube videa neobsahuje žádné video.');
            }
        } catch (Google_Service_Exception $googleServiceException) {
            $this->flashMessageSender->addInfoFlash(t('Nepovedlo připojit ke Google API, takže se nezkontrolovala platnost ID Youtube videa.'));
            $this->flashMessageSender->addInfoFlash(t('Pokud ID Youtube videa není platné, tak se video nebude zobrazovat na frontendu.'));
            $this->flashMessageSender->addInfoFlash(t('ID Youtube videa bylo přesto k produktu uloženo.'));
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
                    'Zadané ID modifikace má neplatný formát (očekává se nenulový počet znaků před i za lomítkem, přičemž část za lomítkem by měla obsahovat jen číslice)'
                );
                return;
            }
        }
        if ($variantId !== null) {
            $existingProductByVariantId = $this->productVariantTropicFacade->findByVariantId($variantId);
            if ($existingProductByVariantId !== null && ($product === null || $product !== null && $existingProductByVariantId->getId() !== $product->getId())) {
                $context->addViolation('Zadané ID modifikace je již v systému přiřazeno jinému produktu');
            }
        }

        if ($this->productVariantTropicFacade->isVariant($variantId)
            && $this->productVariantTropicFacade->findMainVariantByVariantId($variantId) === null
        ) {
            $context->addViolation('Není možné vyvořit variantu, pro kterou neexistuje odpovídající hlavní varianta');
        }
    }
}
