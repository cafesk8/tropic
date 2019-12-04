<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FormTypesBundle\YesNoType;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Form\Admin\PromoCode\PromoCodeFormType;
use Shopsys\FrameworkBundle\Form\Constraints\NotNegativeMoneyAmount;
use Shopsys\FrameworkBundle\Form\DatePickerType;
use Shopsys\FrameworkBundle\Form\DisplayOnlyType;
use Shopsys\FrameworkBundle\Form\DomainType;
use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Form\ValidationGroup;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode as BasePromoCode;
use Shopsys\FrameworkBundle\Twig\PriceExtension;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
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

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Twig\PriceExtension
     */
    private $priceExtension;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Twig\PriceExtension $priceExtension
     */
    public function __construct(Domain $domain, PriceExtension $priceExtension)
    {
        $this->domain = $domain;
        $this->priceExtension = $priceExtension;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $basicInformationsFormGroup = $builder->create('basicInformationsGroup', GroupType::class, [
            'label' => t('Základní informace'),
        ]);
        $basicInformationsFormGroup->add('usageType', ChoiceType::class, [
            'label' => t('Aplikovat na'),
            'choices' => [
                t('Všechny produkty') => PromoCode::USAGE_TYPE_ALL,
                t('Zlevněné produkty') => PromoCode::USAGE_TYPE_WITH_ACTION_PRICE,
                t('Nezlevněné produkty') => PromoCode::USAGE_TYPE_NO_ACTION_PRICE,
            ],
            'multiple' => false,
            'expanded' => false,
            'required' => true,
        ]);

        $basicInformationsFormGroup->add('userType', ChoiceType::class, [
            'label' => t('Aplikovat na uživatele'),
            'choices' => [
                t('Přihlášené i nepřihlášené') => PromoCode::USER_TYPE_ALL,
                t('Pouze přihlášené') => PromoCode::USER_TYPE_LOGGED,
                t('Pouze přihlášené členy Bushman clubu') => PromoCode::USER_TYPE_BUSHMAN_CLUB_MEMBERS,
            ],
            'multiple' => false,
            'expanded' => false,
            'required' => true,
        ]);

        $basicInformationsFormGroup->add('combinable', YesNoType::class, [
            'label' => t('Kombinovatelný'),
        ]);

        $this->addPromoCodeOrCertificateField($builder, $basicInformationsFormGroup);
        $this->extendCodeField($builder, $basicInformationsFormGroup);
        $this->extendPercentField($builder, $basicInformationsFormGroup);
        $this->addNominalDiscountFields($basicInformationsFormGroup, $options['promo_code'], $options['domain_id']);
        $this->addCertificateFields($basicInformationsFormGroup, $options['promo_code'], $options['domain_id']);

        $builder->add($basicInformationsFormGroup);

        if ($options['mass_generate'] === true) {
            $builder->add($this->addMassGenerationGroup($builder));
        }

        if ($options['promo_code'] === null) {
            $basicInformationsFormGroup->add('domainId', DomainType::class, [
                'required' => true,
                'data' => $options['domain_id'],
                'label' => t('Domain'),
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

        $builder->add($this->getRestictionGroup($builder, $options['promo_code'], $options['domain_id']));
        $builder->add($this->getValidationGroup($builder));

        $builder->add('save', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['promo_code', 'domain_id', 'mass_generate'])
            ->setAllowedTypes('promo_code', [BasePromoCode::class, 'null'])
            ->setAllowedTypes('domain_id', 'int')
            ->setAllowedTypes('mass_generate', 'bool')
            ->setDefaults([
                'data_class' => PromoCodeData::class,
                'mass_generate' => false,
                'validation_groups' => function (FormInterface $form) {
                    $validationGroups = [ValidationGroup::VALIDATION_GROUP_DEFAULT];

                    /* @var $promoCodeData \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData */
                    $promoCodeData = $form->getData();

                    if ($promoCodeData->unlimited === false) {
                        $validationGroups[] = self::VALIDATION_GROUP_TYPE_NOT_UNLIMITED;
                    }

                    if ($promoCodeData->useNominalDiscount === true) {
                        $validationGroups[] = self::VALIDATION_GROUP_TYPE_NOMINAL_DISCOUNT;
                    } else {
                        $validationGroups[] = self::VALIDATION_GROUP_TYPE_PERCENT_DISCOUNT;
                    }

                    if ($promoCodeData->type === PromoCodeData::TYPE_CERTIFICATE) {
                        $validationGroups[] = self::VALIDATION_GROUP_TYPE_CERTIFICATE;
                    } else {
                        $validationGroups[] = self::VALIDATION_GROUP_TYPE_PROMO_CODE;
                    }

                    return $validationGroups;
                },
                'constraints' => [
                    new Callback([$this, 'validateMinimalOrderValueForNominalDiscount']),
                ],
            ]);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData $promoCodeData
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public function validateMinimalOrderValueForNominalDiscount(PromoCodeData $promoCodeData, ExecutionContextInterface $context)
    {
        if ($promoCodeData->type === PromoCodeData::TYPE_PROMO_CODE && $promoCodeData->useNominalDiscount && $promoCodeData->minOrderValue < $promoCodeData->nominalDiscount) {
            $context->buildViolation('Minimální hodnota objednávky musí být větší nebo rovna nominální slevě.')
                ->atPath('minOrderValue')
                ->addViolation();
        }
    }

    /**
     * @inheritDoc
     */
    public function getExtendedType(): string
    {
        return PromoCodeFormType::class;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \Symfony\Component\Form\FormBuilderInterface $basicInformationsGroupType
     */
    protected function extendCodeField(FormBuilderInterface $builder, FormBuilderInterface $basicInformationsGroupType): void
    {
        $codeFieldOptions = $builder->get('code')->getOptions();
        $codeFieldOptions['label'] = t('Kód');
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
        $percentFieldOptions['label'] = t('Procentuální sleva');
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
        ];

        $percentFieldType = get_class($builder->get('percent')->getType()->getInnerType());
        $basicInformationsGroupType->add('percent', $percentFieldType, $percentFieldOptions);
        $builder->remove('percent');
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode|null $promoCode
     * @param int|null $domainId
     */
    private function addNominalDiscountFields(FormBuilderInterface $builder, ?PromoCode $promoCode = null, ?int $domainId = null): void
    {
        $builder
            ->add('useNominalDiscount', CheckboxType::class, [
                'label' => t('Použít nominální slevu'),
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
                    new NotNegativeMoneyAmount([
                        'message' => 'Price must be greater or equal to zero',
                        'groups' => self::VALIDATION_GROUP_TYPE_NOMINAL_DISCOUNT,
                    ]),
                    new NotBlank([
                        'message' => 'Vyplňte prosím nominální hodnotu slevy.',
                        'groups' => self::VALIDATION_GROUP_TYPE_NOMINAL_DISCOUNT,
                    ]),
                ],
                'label' => $this->getLabelForMultidomainFieldValue(t('Nominální sleva'), $promoCode, $domainId),
                'attr' => [
                    'class' => 'js-promo-code-input-nominal-discount js-promo-code-promo-code-only',
                ],
            ]);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode|null $promoCode
     * @param int|null $domainId
     */
    private function addCertificateFields(FormBuilderInterface $builder, ?PromoCode $promoCode = null, ?int $domainId = null): void
    {
        $builder
            ->add('certificateSku', TextType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Vyplňte prosím SKU dárkového certifikátu.',
                        'groups' => self::VALIDATION_GROUP_TYPE_CERTIFICATE,
                    ]),
                ],
                'label' => t('SKU certifikátu'),
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
                        'message' => 'Vyplňte prosím nominální hodnotu dárkového certifikátu.',
                        'groups' => self::VALIDATION_GROUP_TYPE_CERTIFICATE,
                    ]),
                ],
                'label' => $this->getLabelForMultidomainFieldValue(t('Hodnota certifikátu'), $promoCode, $domainId),
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
                'required' => false,
                'label' => t('Platný od'),
            ])
            ->add('validTo', DatePickerType::class, [
                'required' => false,
                'label' => t('Platný do'),
            ]);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode|null $promoCode
     * @param int|null $domainId
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function getRestictionGroup(FormBuilderInterface $builder, ?BasePromoCode $promoCode, ?int $domainId): FormBuilderInterface
    {
        return $builder->create('restrictionGroup', GroupType::class, [
            'label' => 'Omezení',
        ])
            ->add('unlimited', CheckboxType::class, [
                'label' => t('Neomezený počet použití'),
                'required' => false,
                'attr' => [
                    'class' => 'js-promo-code-input-unlimited',
                ],
            ])
            ->add('usageLimit', IntegerType::class, [
                'label' => t('Maximální počet použití'),
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
                        'message' => 'Vyplňte prosím množství.',
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
                'label' => $this->getLabelForMultidomainFieldValue(t('Minimální hodnota objednávky'), $promoCode, $domainId),
            ]);
    }

    /**
     * @param string $prefix
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode|null $promoCode
     * @param int|null $domainId
     * @return string
     */
    private function getLabelForMultidomainFieldValue(string $prefix, ?BasePromoCode $promoCode = null, ?int $domainId = null): string
    {
        if ($promoCode !== null) {
            return $prefix . ' (' . $this->priceExtension->getCurrencySymbolByDomainId($domainId) . ')';
        } else {
            return $prefix . ' ' . t('v měně dle zvolené domény');
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
            'label' => t('Hromadné generování kupónu'),
            'position' => 'first',
        ]);

        $builderMassPromoCodeGroup
            ->add('prefix', TextType::class, [
                'label' => t('Prefix (např. "JARO_")'),
                'required' => false,
            ])
            ->add('quantity', IntegerType::class, [
                'label' => t('Počet generovaných kupónů'),
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Vyplňte prosím množství.',
                    ]),
                    new Range([
                        'min' => 1,
                        'max' => PromoCode::MAX_CODES_GENERATE,
                    ]),
                ],
                'invalid_message' => 'Zadejte prosím celé číslo.',
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
                t('Slevový kód') => PromoCodeData::TYPE_PROMO_CODE,
                t('Dárkový certifikát') => PromoCodeData::TYPE_CERTIFICATE,
            ],
            'attr' => [
                'class' => 'js-promo-code-promo-code-or-certificate',
            ],
            'position' => 'first',
        ]);
    }
}
