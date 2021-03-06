<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\Order\PromoCode\PromoCode;
use App\Model\Order\PromoCode\PromoCodeData;
use App\Model\Order\PromoCode\PromoCodeFacade;
use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Form\Admin\PromoCode\PromoCodeFormType;
use Shopsys\FrameworkBundle\Form\CategoriesType;
use Shopsys\FrameworkBundle\Form\Constraints\MoneyRange;
use Shopsys\FrameworkBundle\Form\Constraints\NotNegativeMoneyAmount;
use Shopsys\FrameworkBundle\Form\DatePickerType;
use Shopsys\FrameworkBundle\Form\DisplayOnlyType;
use Shopsys\FrameworkBundle\Form\DomainType;
use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Form\ProductsType;
use Shopsys\FrameworkBundle\Form\ValidationGroup;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode as BasePromoCode;
use Shopsys\FrameworkBundle\Model\Product\Brand\Brand;
use Shopsys\FrameworkBundle\Model\Product\Brand\BrandFacade;
use Shopsys\FrameworkBundle\Twig\PriceExtension;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PromoCodeFormTypeExtension extends AbstractTypeExtension
{
    public const VALIDATION_GROUP_TYPE_NOT_UNLIMITED = 'NOT_UNLIMITED';

    public const VALIDATION_GROUP_TYPE_NOMINAL_DISCOUNT = 'NOMINAL_DISCOUNT';

    public const VALIDATION_GROUP_TYPE_PERCENT_DISCOUNT = 'PERCENT_DISCOUNT';

    public const VALIDATION_GROUP_TYPE_CERTIFICATE = 'CERTIFICATE';

    public const VALIDATION_GROUP_TYPE_PROMO_CODE = 'PROMO_CODE';

    public const VALIDATION_GROUP_TYPE_LIMIT_BRANDS = 'LIMIT_BRANDS';

    private Domain $domain;

    private PriceExtension $priceExtension;

    private BrandFacade $brandFacade;

    private AdminDomainTabsFacade $adminDomainTabsFacade;

    private ?PromoCode $promoCode;

    private PromoCodeFacade $promoCodeFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Twig\PriceExtension $priceExtension
     * @param \App\Model\Product\Brand\BrandFacade $brandFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \App\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
     */
    public function __construct(
        Domain $domain,
        PriceExtension $priceExtension,
        BrandFacade $brandFacade,
        AdminDomainTabsFacade $adminDomainTabsFacade,
        PromoCodeFacade $promoCodeFacade
    ) {
        $this->domain = $domain;
        $this->priceExtension = $priceExtension;
        $this->brandFacade = $brandFacade;
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
        $this->promoCodeFacade = $promoCodeFacade;
    }

    /**
     * @param \Symfony\Component\Form\FormView $view
     * @param \Symfony\Component\Form\FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr']['class'] = 'js-promo-code-form';
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->promoCode = $options['promo_code'];

        $domainId = $this->adminDomainTabsFacade->getSelectedDomainId();

        $basicInformationsFormGroup = $builder->create('basicInformationsGroup', GroupType::class, [
            'label' => t('Z??kladn?? informace'),
        ]);
        $this->addPromoCodeOrCertificateField($builder, $basicInformationsFormGroup);

        $basicInformationsFormGroup->add('userType', ChoiceType::class, [
            'label' => t('Aplikovat na u??ivatele'),
            'choices' => [
                t('P??ihl????en?? i nep??ihl????en??') => PromoCode::USER_TYPE_ALL,
                t('Pouze p??ihl????en??') => PromoCode::USER_TYPE_LOGGED,
            ],
            'multiple' => false,
            'expanded' => false,
            'required' => true,
        ]);

        $this->extendCodeField($builder, $basicInformationsFormGroup);
        $this->extendPercentField($builder, $basicInformationsFormGroup);
        $this->addNominalDiscountFields($basicInformationsFormGroup, $options['promo_code'], $domainId);
        $this->addCertificateFields($basicInformationsFormGroup, $options['promo_code'], $domainId);

        $basicInformationsFormGroup->add('limitType', ChoiceType::class, [
            'label' => t('Omezit pou??it??'),
            'attr' => [
                'class' => 'js-promo-code-input-use-limit-type js-promo-code-promo-code-only',
            ],
            'choices' => [
                t('Neomezovat') => PromoCode::LIMIT_TYPE_ALL,
                t('Podle kategorie') => PromoCode::LIMIT_TYPE_CATEGORIES,
                t('Podle zna??ky') => PromoCode::LIMIT_TYPE_BRANDS,
                t('Konkr??tn?? produkty') => PromoCode::LIMIT_TYPE_PRODUCTS,
            ],
            'multiple' => false,
            'expanded' => false,
            'required' => true,
        ]);

        $builder->add($basicInformationsFormGroup);
        $this->addLimitTypeGroup($builder);

        if ($options['mass_generate'] === true) {
            $builder->add($this->addMassGenerationGroup($builder));
        }

        if ($options['promo_code'] === null) {
            $basicInformationsFormGroup->add('domainId', DomainType::class, [
                'required' => true,
                'data' => $domainId,
                'label' => t('Domain'),
                'position' => 'first',
                'disabled' => true,
            ]);
        } else {
            $basicInformationsFormGroup
                ->add('id', DisplayOnlyType::class, [
                    'data' => $options['promo_code']->getId(),
                    'label' => t('ID'),
                    'position' => 'first',
                ])
                ->add('domain', DisplayOnlyType::class, [
                    'data' => $this->domain->getDomainConfigById($options['promo_code']->getDomainId())->getName(),
                    'label' => t('Domain'),
                    'position' => ['after' => 'id'],
                ]);
        }

        $builder->add($this->getValidationGroup($builder));
        $builder->add($this->getRestrictionGroup($builder, $options['promo_code'], $domainId));
        $builder->add('save', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['promo_code', 'mass_generate'])
            ->setAllowedTypes('promo_code', [BasePromoCode::class, 'null'])
            ->setAllowedTypes('mass_generate', 'bool')
            ->setDefaults([
                'data_class' => PromoCodeData::class,
                'mass_generate' => false,
                'validation_groups' => function (FormInterface $form) {
                    $validationGroups = [ValidationGroup::VALIDATION_GROUP_DEFAULT];

                    /* @var $promoCodeData \App\Model\Order\PromoCode\PromoCodeData */
                    $promoCodeData = $form->getData();

                    if ($promoCodeData->unlimited === false) {
                        $validationGroups[] = self::VALIDATION_GROUP_TYPE_NOT_UNLIMITED;
                    }

                    if ($promoCodeData->type === PromoCodeData::TYPE_PROMO_CODE) {
                        if ($promoCodeData->useNominalDiscount) {
                            $validationGroups[] = self::VALIDATION_GROUP_TYPE_NOMINAL_DISCOUNT;
                        } else {
                            $validationGroups[] = self::VALIDATION_GROUP_TYPE_PERCENT_DISCOUNT;
                        }
                    }

                    if ($promoCodeData->type === PromoCodeData::TYPE_CERTIFICATE) {
                        $validationGroups[] = self::VALIDATION_GROUP_TYPE_CERTIFICATE;
                    } else {
                        $validationGroups[] = self::VALIDATION_GROUP_TYPE_PROMO_CODE;
                        if ($promoCodeData->useNominalDiscount === true) {
                            $validationGroups[] = self::VALIDATION_GROUP_TYPE_NOMINAL_DISCOUNT;
                        } else {
                            $validationGroups[] = self::VALIDATION_GROUP_TYPE_PERCENT_DISCOUNT;
                        }
                    }

                    if ($promoCodeData->limitType === PromoCode::LIMIT_TYPE_BRANDS) {
                        $validationGroups[] = self::VALIDATION_GROUP_TYPE_LIMIT_BRANDS;
                    }

                    return $validationGroups;
                },
                'constraints' => [
                    new Callback([$this, 'validateMinimalOrderValueForNominalDiscount']),
                ],
            ]);
    }

    /**
     * @param \App\Model\Order\PromoCode\PromoCodeData $promoCodeData
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public function validateMinimalOrderValueForNominalDiscount(PromoCodeData $promoCodeData, ExecutionContextInterface $context)
    {
        if ($promoCodeData->type === PromoCodeData::TYPE_PROMO_CODE && $promoCodeData->useNominalDiscount && $promoCodeData->minOrderValue < $promoCodeData->nominalDiscount) {
            $context->buildViolation('Minim??ln?? hodnota objedn??vky mus?? b??t v??t???? nebo rovna nomin??ln?? slev??.')
                ->atPath('minOrderValue')
                ->addViolation();
        }
    }

    /**
     * @inheritDoc
     */
    public static function getExtendedTypes(): iterable
    {
        yield PromoCodeFormType::class;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \Symfony\Component\Form\FormBuilderInterface $basicInformationsGroupType
     */
    protected function extendCodeField(FormBuilderInterface $builder, FormBuilderInterface $basicInformationsGroupType): void
    {
        $codeFieldOptions = $builder->get('code')->getOptions();
        $codeFieldOptions['label'] = t('K??d');
        $codeFieldOptions['constraints'] = [
            new Constraints\NotBlank([
                'message' => 'Please enter code',
            ]),
            new Constraints\Callback([$this, 'validateUniquePromoCode']),
        ];
        $codeFieldType = get_class($builder->get('code')->getType()->getInnerType());
        $basicInformationsGroupType->add('code', $codeFieldType, $codeFieldOptions);
        $builder->remove('code');
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \Symfony\Component\Form\FormBuilderInterface $basicInformationsGroupType
     */
    private function extendPercentField(FormBuilderInterface $builder, FormBuilderInterface $basicInformationsGroupType): void
    {
        $percentFieldOptions = $builder->get('percent')->getOptions();
        $percentFieldOptions['label'] = t('Procentu??ln?? sleva');
        $percentFieldOptions['attr'] = ['class' => 'js-promo-code-input-percent-discount js-promo-code-promo-code-only'];

        $percentFieldOptions['constraints'] = [
            new NotBlank([
                'message' => 'Please enter discount percentage',
                'groups' => self::VALIDATION_GROUP_TYPE_PERCENT_DISCOUNT,
            ]),
            new Range([
                'min' => 0,
                'max' => 100,
                'groups' => self::VALIDATION_GROUP_TYPE_PERCENT_DISCOUNT,
            ]),
            new GreaterThan([
                'groups' => self::VALIDATION_GROUP_TYPE_PERCENT_DISCOUNT,
                'value' => 0,
            ]),
        ];

        $percentFieldType = get_class($builder->get('percent')->getType()->getInnerType());
        $basicInformationsGroupType->add('percent', $percentFieldType, $percentFieldOptions);
        $builder->remove('percent');
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Order\PromoCode\PromoCode|null $promoCode
     * @param int|null $domainId
     */
    private function addNominalDiscountFields(FormBuilderInterface $builder, ?PromoCode $promoCode = null, ?int $domainId = null): void
    {
        $builder
            ->add('useNominalDiscount', CheckboxType::class, [
                'label' => t('Pou????t nomin??ln?? slevu'),
                'required' => false,
                'attr' => [
                    'class' => 'js-promo-code-input-use-nominal-discount js-promo-code-promo-code-only',
                ],
                'position' => ['before' => 'percent'],
            ])
            ->add('nominalDiscount', MoneyType::class, [
                'scale' => 6,
                'required' => true,
                'invalid_message' => 'Please enter price in correct format (positive number with decimal separator)',
                'constraints' => [
                    new MoneyRange([
                        'min' => Money::create(1),
                        'groups' => self::VALIDATION_GROUP_TYPE_NOMINAL_DISCOUNT,
                    ]),
                    new NotBlank([
                        'message' => 'Vypl??te pros??m nomin??ln?? hodnotu slevy.',
                        'groups' => self::VALIDATION_GROUP_TYPE_NOMINAL_DISCOUNT,
                    ]),
                ],
                'label' => $this->getLabelForMultidomainFieldValue(t('Nomin??ln?? sleva'), $promoCode, $domainId),
                'attr' => [
                    'class' => 'js-promo-code-input-nominal-discount js-promo-code-promo-code-only',
                ],
            ]);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Order\PromoCode\PromoCode|null $promoCode
     * @param int|null $domainId
     */
    private function addCertificateFields(FormBuilderInterface $builder, ?PromoCode $promoCode = null, ?int $domainId = null): void
    {
        $builder
            ->add('certificateSku', TextType::class, [
                'required' => false,
                'label' => t('SKU certifik??tu'),
                'attr' => [
                    'class' => 'js-promo-code-certificate-field',
                ],
                'position' => ['after' => 'nominalDiscount'],
            ])
            ->add('certificateValue', MoneyType::class, [
                'scale' => 6,
                'required' => true,
                'invalid_message' => 'Please enter price in correct format (positive number with decimal separator)',
                'constraints' => [
                    new NotNegativeMoneyAmount([
                        'message' => 'Price must be greater or equal to zero',
                        'groups' => self::VALIDATION_GROUP_TYPE_CERTIFICATE,
                    ]),
                    new NotBlank([
                        'message' => 'Vypl??te pros??m nomin??ln?? hodnotu d??rkov??ho poukazu.',
                        'groups' => self::VALIDATION_GROUP_TYPE_CERTIFICATE,
                    ]),
                ],
                'label' => $this->getLabelForMultidomainFieldValue(t('Hodnota poukazu'), $promoCode, $domainId),
                'attr' => [
                    'class' => 'js-promo-code-certificate-field',
                ],
                'position' => ['after' => 'certificateSku'],
            ]);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function getValidationGroup(FormBuilderInterface $builder): FormBuilderInterface
    {
        return $builder->create('validGroup', GroupType::class, [
            'label' => t('Platnost'),
        ])
            ->add('validFrom', DatePickerType::class, [
                'view_timezone' => 'UTC',
                'required' => false,
                'label' => t('Platn?? od'),
            ])
            ->add('validTo', DatePickerType::class, [
                'view_timezone' => 'UTC',
                'required' => false,
                'label' => t('Platn?? do'),
            ]);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \App\Model\Order\PromoCode\PromoCode|null $promoCode
     * @param int|null $domainId
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function getRestrictionGroup(FormBuilderInterface $builder, ?BasePromoCode $promoCode, ?int $domainId): FormBuilderInterface
    {
        return $builder->create('restrictionGroup', GroupType::class, [
            'label' => 'Omezen??',
        ])
            ->add('unlimited', CheckboxType::class, [
                'label' => t('Neomezen?? po??et pou??it??'),
                'required' => false,
                'attr' => [
                    'class' => 'js-promo-code-input-unlimited',
                ],
            ])
            ->add('usageLimit', IntegerType::class, [
                'label' => t('Maxim??ln?? po??et pou??it??'),
                'required' => true,
                'attr' => [
                    'class' => 'js-promo-code-input-usage-limit',
                ],
                'constraints' => [
                    new GreaterThanOrEqual([
                        'value' => 1,
                        'groups' => self::VALIDATION_GROUP_TYPE_NOT_UNLIMITED,
                    ]),
                    new NotBlank([
                        'message' => 'Vypl??te pros??m mno??stv??.',
                        'groups' => self::VALIDATION_GROUP_TYPE_NOT_UNLIMITED,
                    ]),
                ],
            ])
            ->add('minOrderValue', MoneyType::class, [
                'scale' => 6,
                'required' => false,
                'invalid_message' => 'Please enter price in correct format (positive number with decimal separator)',
                'constraints' => [
                    new NotNegativeMoneyAmount([
                        'message' => 'Price must be greater or equal to zero',
                        'groups' => self::VALIDATION_GROUP_TYPE_PROMO_CODE,
                    ]),
                ],
                'attr' => [
                    'class' => 'js-promo-code-promo-code-only',
                ],
                'label' => $this->getLabelForMultidomainFieldValue(t('Minim??ln?? hodnota objedn??vky'), $promoCode, $domainId),
            ]);
    }

    /**
     * @param string $prefix
     * @param \App\Model\Order\PromoCode\PromoCode|null $promoCode
     * @param int|null $domainId
     * @return string
     */
    private function getLabelForMultidomainFieldValue(string $prefix, ?BasePromoCode $promoCode = null, ?int $domainId = null): string
    {
        if ($promoCode !== null) {
            return $prefix . ' (' . $this->priceExtension->getCurrencySymbolByDomainId($domainId) . ')';
        } else {
            return $prefix . ' ' . t('v m??n?? dle zvolen?? dom??ny');
        }
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function addMassGenerationGroup(FormBuilderInterface $builder): FormBuilderInterface
    {
        $builder->get('basicInformationsGroup')->remove('code');

        $builderMassPromoCodeGroup = $builder->create('massPromoCodeGroup', GroupType::class, [
            'label' => t('Hromadn?? generov??n?? kup??nu'),
            'position' => 'first',
        ]);

        $builderMassPromoCodeGroup
            ->add('prefix', TextType::class, [
                'label' => t('Prefix (nap??. "JARO_")'),
                'required' => false,
            ])
            ->add('quantity', IntegerType::class, [
                'label' => t('Po??et generovan??ch kup??n??'),
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Vypl??te pros??m mno??stv??.',
                    ]),
                    new Range([
                        'min' => 1,
                        'max' => PromoCode::MAX_CODES_GENERATE,
                    ]),
                ],
                'invalid_message' => 'Zadejte pros??m cel?? ????slo.',
            ]);

        return $builderMassPromoCodeGroup;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \Symfony\Component\Form\FormBuilderInterface $basicInformationsFormGroup
     */
    private function addPromoCodeOrCertificateField(FormBuilderInterface $builder, FormBuilderInterface $basicInformationsFormGroup)
    {
        $basicInformationsFormGroup->add('type', ChoiceType::class, [
            'label' => t('Vyberte typ poukazu'),
            'required' => true,
            'choices' => [
                t('Slevov?? k??d') => PromoCodeData::TYPE_PROMO_CODE,
                t('D??rkov?? poukaz') => PromoCodeData::TYPE_CERTIFICATE,
            ],
            'attr' => [
                'class' => 'js-promo-code-promo-code-or-certificate',
            ],
        ]);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     */
    private function addLimitTypeGroup(FormBuilderInterface $builder)
    {
        $brandLimitsGroups = $builder->create('brandlimitsGroup', GroupType::class, [
            'attr' => [
                'class' => 'js-promo-code-limit-brands-group js-promo-code-input-limit-type js-promo-code-promo-code-only',
            ],
            'label' => t('Omezen?? na zna??ky'),
        ]);

        $brandLimitsInput = $brandLimitsGroups->create('brandLimits', ChoiceType::class, [
            'attr' => [
                'class' => 'js-promo-code-input-limit-brands js-promo-code-input-limit-type js-promo-code-promo-code-only',
            ],
            'choices' => $this->brandFacade->getAll(),
            'label' => t('Omezen?? na zna??ky'),
            'mapped' => true,
            'multiple' => true,
            'required' => true,
            'constraints' => [
                new NotBlank(['groups' => self::VALIDATION_GROUP_TYPE_LIMIT_BRANDS]),
            ],
            'choice_label' => function (Brand $brand) {
                return $brand->getName();
            },
        ]);

        $brandLimitsGroups->add($brandLimitsInput);
        $builder->add($brandLimitsGroups);

        $categoryLimitsGroups = $builder->create('categorylimitsGroup', GroupType::class, [
            'attr' => [
                'class' => 'js-promo-code-limit-categories-group js-promo-code-input-limit-type js-promo-code-promo-code-only',
            ],
            'label' => t('Omezen?? na kategorie'),
        ]);

        $categoryLimitsGroups->add('categoryLimits', CategoriesType::class, [
            'attr' => [
                'class' => 'js-promo-code-input-limit-categories js-promo-code-input-limit-type js-promo-code-promo-code-only',
            ],
            'domain_id' => $this->domain->getId(),
            'label' => t('Kategorie'),
            'mapped' => true,
        ]);

        $builder->add($categoryLimitsGroups);

        $productLimits = $builder->create('productLimits', ProductsType::class, [
            'attr' => [
                'class' => 'js-promo-code-input-limit-products js-promo-code-input-limit-type js-promo-code-promo-code-only',
            ],
            'label' => t('Omezen?? na produkty'),
            'mapped' => true,
        ]);

        $builder->add($productLimits);
    }

    /**
     * @param string $promoCodeValue
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public function validateUniquePromoCode(string $promoCodeValue, ExecutionContextInterface $context): void
    {
        if ($this->promoCode === null || $promoCodeValue !== $this->promoCode->getCode()) {
            $promoCode = $this->promoCodeFacade->findPromoCodeByCodeAndDomainId($promoCodeValue, $this->adminDomainTabsFacade->getSelectedDomainId());

            if ($promoCode !== null) {
                $context->addViolation('Promo code with this code already exists');
            }
        }
    }
}
