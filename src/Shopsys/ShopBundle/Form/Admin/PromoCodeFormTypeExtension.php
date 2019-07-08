<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

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
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class PromoCodeFormTypeExtension extends AbstractTypeExtension
{
    public const VALIDATION_GROUP_TYPE_NOT_UNLIMITED = 'NOT_UNLIMITED';

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

        $this->extendCodeField($builder, $basicInformationsFormGroup);
        $this->extendPercentField($builder, $basicInformationsFormGroup);

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

                    return $validationGroups;
                },
            ]);
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
        $percentFieldOptions['label'] = t('Sleva');
        $percentFieldType = get_class($builder->get('percent')->getType()->getInnerType());
        $basicInformationsGroupType->add('percent', $percentFieldType, $percentFieldOptions);
        $builder->remove('percent');
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
                    new NotNegativeMoneyAmount(['message' => 'Price must be greater or equal to zero']),
                ],
                'label' => $this->getLabelForMinOrderValue($promoCode, $domainId),
            ]);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode|null $promoCode
     * @param int|null $domainId
     * @return string
     */
    private function getLabelForMinOrderValue(?BasePromoCode $promoCode, ?int $domainId): string
    {
        if ($promoCode !== null) {
            return t('Minimální hodnota objednávky') . ' (' . $this->priceExtension->getCurrencySymbolByDomainId($domainId) . ')';
        } else {
            return t('Minimální hodnota objednávky v měně dle zvolené domény');
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
                        'message' => t('Vyplňte prosím množství.'),
                    ]),
                    new Range([
                        'min' => 1,
                        'max' => PromoCode::MAX_CODES_GENERATE,
                    ]),
                ],
                'invalid_message' => t('Zadejte prosím celé číslo.'),
            ]);

        return $builderMassPromoCodeGroup;
    }
}
