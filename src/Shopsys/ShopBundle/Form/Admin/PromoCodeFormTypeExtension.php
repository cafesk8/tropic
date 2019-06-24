<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FrameworkBundle\Form\Admin\PromoCode\PromoCodeFormType;
use Shopsys\FrameworkBundle\Form\ValidationGroup;
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
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('unlimited', CheckboxType::class, [
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

        $builder->add('save', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
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
}
