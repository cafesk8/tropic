<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Admin;

use Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade;
use Shopsys\FrameworkBundle\Form\Constraints\NotNegativeMoneyAmount;
use Shopsys\FrameworkBundle\Form\ProductType;
use Shopsys\ShopBundle\Model\Product\PromoProduct\PromoProductData;
use Shopsys\ShopBundle\Twig\PriceExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PromoProductFormType extends AbstractType
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade
     */
    private $adminDomainTabsFacade;

    /**
     * @var \Shopsys\ShopBundle\Twig\PriceExtension
     */
    private $priceExtension;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\AdminDomainTabsFacade $adminDomainTabsFacade
     * @param \Shopsys\ShopBundle\Twig\PriceExtension $priceExtension
     */
    public function __construct(
        AdminDomainTabsFacade $adminDomainTabsFacade,
        PriceExtension $priceExtension
    ) {
        $this->adminDomainTabsFacade = $adminDomainTabsFacade;
        $this->priceExtension = $priceExtension;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currencySymbol = $this->priceExtension->getCurrencyCodeByDomainId($this->adminDomainTabsFacade->getSelectedDomainId());

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
                'scale' => 6,
                'required' => false,
                'label' => t(sprintf('Cena promo produktu (v %s)', $currencySymbol)),
                'invalid_message' => 'Please enter price in correct format (positive number with decimal separator)',
                'constraints' => [
                    new NotNegativeMoneyAmount(['message' => 'Price must be greater or equal to zero']),
                ],
            ])
            ->add('minimalCartPrice', MoneyType::class, [
                'scale' => 6,
                'required' => false,
                'label' => t(sprintf('Minimální cena košíku (v %s)', $currencySymbol)),
                'invalid_message' => 'Please enter price in correct format (positive number with decimal separator)',
                'constraints' => [
                    new NotNegativeMoneyAmount(['message' => 'Price must be greater or equal to zero']),
                ],
            ])
            ->add('save', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => PromoProductData::class,
                'attr' => ['novalidate' => 'novalidate'],
            ]);
    }
}
