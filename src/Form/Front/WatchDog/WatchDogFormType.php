<?php

declare(strict_types=1);

namespace App\Form\Front\WatchDog;

use App\Model\Product\Product;
use App\Model\WatchDog\WatchDogData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class WatchDogFormType extends AbstractType
{
    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \App\Model\Product\Product $product */
        $product = $options['product'];

        $builder
            ->add('priceWatcher', CheckboxType::class, [
                'data' => true,
                'label' => t('Hlídat snížení ceny'),
            ])
            ->add('targetedDiscount', MoneyType::class, [
                'label' => t('Informovat až po snížení ceny o'),
            ])
            ->add('availabilityWatcher', CheckboxType::class, [
                'data' => $product->getCalculatedSellingDenied(),
                'label' => t('Hlídat naskladnění'),
            ])
            ->add('productId', HiddenType::class, [
                'data' => $product->getId(),
                'mapped' => false,
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Email(),
                    new NotBlank(),
                ],
                'data' => $options['email'],
                'label' => t('Váš e-mail'),
                'required' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => t('Odeslat'),
            ]);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'attr' => ['novalidate' => 'novalidate'],
                'data_class' => WatchDogData::class,
            ])
            ->setRequired('email')
            ->setAllowedTypes('email', ['string', 'null'])
            ->setRequired('product')
            ->setAllowedTypes('product', Product::class);
    }
}
