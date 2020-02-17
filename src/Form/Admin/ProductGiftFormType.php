<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\Product\ProductGift\ProductGift;
use App\Model\Product\ProductGift\ProductGiftData;
use Shopsys\FormTypesBundle\YesNoType;
use Shopsys\FrameworkBundle\Form\ProductsType;
use Shopsys\FrameworkBundle\Form\ProductType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProductGiftFormType extends AbstractType
{
    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /* @var \App\Model\Product\ProductGift\ProductGift|null $productGift */
        $productGift = $options['productGift'];
        $gift = $productGift === null ? null : $productGift->getGift();

        $builder
            ->add('title', TextType::class, [
                'required' => false,
                'label' => t('Nepovinný název pro administraci'),
            ])
            ->add('active', YesNoType::class, [
                'required' => true,
                'label' => t('Aktivní'),
            ])
            ->add('gift', ProductType::class, [
                'required' => true,
                'label' => t('Dárek'),
                'allow_main_variants' => true,
                'allow_variants' => true,
                'enableRemove' => false,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('products', ProductsType::class, [
                'required' => false,
                'main_product' => $gift,
                'allow_main_variants' => true,
                'allow_variants' => false,
                'label_button_add' => t('Přidat produkt'),
                'label' => t('Produkty'),
            ])
            ->add('save', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('productGift')
            ->setAllowedTypes('productGift', [ProductGift::class, 'null'])
            ->setDefaults([
                'data_class' => ProductGiftData::class,
                'attr' => ['novalidate' => 'novalidate'],
            ]);
    }
}
