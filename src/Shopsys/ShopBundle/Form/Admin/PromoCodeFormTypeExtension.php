<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Form\Admin\PromoCode\PromoCodeFormType;
use Shopsys\FrameworkBundle\Form\DatePickerType;
use Shopsys\FrameworkBundle\Form\DisplayOnlyType;
use Shopsys\FrameworkBundle\Form\DomainType;
use Shopsys\FrameworkBundle\Form\GroupType;
use Shopsys\FrameworkBundle\Form\ValidationGroup;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class PromoCodeFormTypeExtension extends AbstractTypeExtension
{
    public const VALIDATION_GROUP_TYPE_NOT_UNLIMITED = 'NOT_UNLIMITED';

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(Domain $domain)
    {
        $this->domain = $domain;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $this->extendCodeField($builder);
        $this->extendPercentField($builder);

        if ($options['promo_code'] === null) {
            $builder->add('domainId', DomainType::class, [
                'required' => true,
                'data' => $options['domain_id'],
                'label' => t('Domain'),
            ]);
        } else {
            $builder
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

        $builder->add($this->getRestictionGroup($builder));
        $builder->add($this->getValidationGroup($builder));

        $builder->add('save', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired(['promo_code', 'domain_id'])
            ->setAllowedTypes('promo_code', [PromoCode::class, 'null'])
            ->setAllowedTypes('domain_id', 'int')
            ->setDefaults([
            'data_class' => PromoCodeData::class,
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
     */
    protected function extendCodeField(FormBuilderInterface $builder): void
    {
        $codeFieldOptions = $builder->get('code')->getOptions();
        $codeFieldOptions['label'] = t('Kód');
        $codeFieldType = get_class($builder->get('code')->getType()->getInnerType());
        $builder->add('code', $codeFieldType, $codeFieldOptions);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     */
    private function extendPercentField(FormBuilderInterface $builder): void
    {
        $percentFieldOptions = $builder->get('percent')->getOptions();
        $percentFieldOptions['label'] = t('Sleva');
        $percentFieldType = get_class($builder->get('percent')->getType()->getInnerType());
        $builder->add('percent', $percentFieldType, $percentFieldOptions);
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
     * @return \Symfony\Component\Form\FormBuilderInterface
     */
    private function getRestictionGroup(FormBuilderInterface $builder): FormBuilderInterface
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
                        'message' => t('Vyplňte prosím množství.'),
                        'groups' => self::VALIDATION_GROUP_TYPE_NOT_UNLIMITED,
                    ]),
                ],
            ]);
    }
}
