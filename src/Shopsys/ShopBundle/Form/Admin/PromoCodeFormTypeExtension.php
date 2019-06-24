<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FrameworkBundle\Form\Admin\PromoCode\PromoCodeFormType;
use Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeData;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;

class PromoCodeFormTypeExtension extends AbstractTypeExtension
{
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
        ])
        ->add('usageLimit', IntegerType::class, [
            'label' => t('Maximální počet použití'),
            'required' => true,
            'constraints' => [
                new GreaterThanOrEqual(['value' => 1]),
                new NotBlank(['message' => t('Vyplňte prosím množství.')]),
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
