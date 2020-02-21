<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\Order\Gift\OrderGift;
use App\Model\Order\Gift\OrderGiftData;
use App\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FormTypesBundle\YesNoType;
use Shopsys\FrameworkBundle\Form\Constraints\NotNegativeMoneyAmount;
use Shopsys\FrameworkBundle\Form\DomainType;
use Shopsys\FrameworkBundle\Form\ProductsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class OrderGiftFormType extends AbstractType
{
    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    protected $currencyFacade;

    /**
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     */
    public function __construct(CurrencyFacade $currencyFacade)
    {
        $this->currencyFacade = $currencyFacade;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $orderGift = $options['orderGift'];
        $domainId = $options['domainId'];
        $builder
            ->add('domainId', DomainType::class, [
                'label' => t('Doména'),
                'disabled' => $orderGift !== null,
            ])
            ->add('enabled', YesNoType::class, [
                'label' => t('Aktivní'),
            ])
            ->add('priceLevelWithVat', MoneyType::class, [
                'label' => t('Hladina ceny objednávky'),
                'scale' => 6,
                'invalid_message' => 'Please enter price in correct format (positive number with decimal separator)',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Zadejte prosím hladinu ceny objednávky',
                    ]),
                    new NotNegativeMoneyAmount(['message' => 'Price must be greater or equal to zero']),
                ],
                'currency' => $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainId)->getCode(),
            ])
            ->add('products', ProductsType::class, [
                'label' => t('Nabízené produkty'),
                'constraints' => [
                    new NotBlank([
                        'message' => 'Vyberte prosím nějaké produkty',
                    ]),
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
            ->setRequired('orderGift')
            ->setRequired('domainId')
            ->setAllowedTypes('orderGift', [OrderGift::class, 'null'])
            ->setAllowedTypes('domainId', ['int'])
            ->setDefaults([
                'data_class' => OrderGiftData::class,
                'attr' => ['novalidate' => 'novalidate'],
            ]);
    }
}
