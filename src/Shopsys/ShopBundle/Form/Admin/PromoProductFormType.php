<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FrameworkBundle\Form\ProductType;
use Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProduct;
use Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PromoProductFormType extends AbstractType
{
    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /* @var \Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProduct|null $promoProduct */
        $promoProduct = $options['promoProduct'];
        $product = $promoProduct === null ? null : $promoProduct->getProduct();

        $builder
            ->add('product', ProductType::class, [
                'required' => true,
                'label' => t('Promo produkt'),
                'allow_main_variants' => true,
                'allow_variants' => true,
                'enableRemove' => false,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('price', MoneyType::class, [
                'required' => false,
                'label' => t('Cena promo produktu'),
            ])
            ->add('minimalCartPrice', MoneyType::class, [
                'required' => false,
                'label' => t('Minimální cena košíku'),
            ])
            ->add('save', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('promoProduct')
            ->setAllowedTypes('promoProduct', [PromoProduct::class, 'null'])
            ->setDefaults([
                'data_class' => PromoProductData::class,
                'attr' => ['novalidate' => 'novalidate'],
            ]);
    }
}
