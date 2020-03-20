<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\Order\Gift\OrderGift;
use App\Model\Order\Gift\OrderGiftData;
use App\Model\Order\Gift\OrderGiftFacade;
use App\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FormTypesBundle\YesNoType;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Form\Constraints\NotNegativeMoneyAmount;
use Shopsys\FrameworkBundle\Form\DomainType;
use Shopsys\FrameworkBundle\Form\ProductsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class OrderGiftFormType extends AbstractType
{
    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    protected $currencyFacade;

    /**
     * @var \App\Model\Order\Gift\OrderGiftFacade
     */
    protected $orderGiftFacade;

    /**
     * @var \App\Model\Order\Gift\OrderGift|null
     */
    protected $orderGift;

    /**
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \App\Model\Order\Gift\OrderGiftFacade $orderGiftFacade
     */
    public function __construct(CurrencyFacade $currencyFacade, OrderGiftFacade $orderGiftFacade)
    {
        $this->currencyFacade = $currencyFacade;
        $this->orderGiftFacade = $orderGiftFacade;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->orderGift = $options['orderGift'];
        $domainId = $options['domainId'];
        $builder
            ->add('domainId', DomainType::class, [
                'label' => t('Doména'),
                'disabled' => $this->orderGift !== null,
                'attr' => [
                    'class' => 'js-order-gift-domain-id',
                ],
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
                    new Callback([$this, 'validateUniqueLevel']),
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

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $priceLevel
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public function validateUniqueLevel(?Money $priceLevel, ExecutionContextInterface $context): void
    {
        if ($priceLevel === null) {
            return;
        }
        /** @var \Symfony\Component\Form\Form $form */
        $form = $context->getRoot();
        /** @var \App\Model\Order\Gift\OrderGiftData $orderGiftData */
        $orderGiftData = $form->getData();

        $exceptLevel = null;
        if ($this->orderGift instanceof OrderGift) {
            $exceptLevel = $this->orderGift->getPriceLevelWithVat();
        }

        $allLevels = $this->orderGiftFacade->getAllLevelsOnDomainExceptLevel($orderGiftData->domainId, $exceptLevel);
        foreach ($allLevels as $level) {
            if ($priceLevel->getAmount() === $level->getAmount()) {
                $context->addViolation('Tato hladina ceny objednávky již existuje');
                return;
            }
        }
    }
}
