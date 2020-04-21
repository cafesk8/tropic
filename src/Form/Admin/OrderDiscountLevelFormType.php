<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\Order\Discount\OrderDiscountLevel;
use App\Model\Order\Discount\OrderDiscountLevelData;
use App\Model\Order\Discount\OrderDiscountLevelFacade;
use App\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FormTypesBundle\YesNoType;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Form\Constraints\NotNegativeMoneyAmount;
use Shopsys\FrameworkBundle\Form\DomainType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class OrderDiscountLevelFormType extends AbstractType
{
    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @var \App\Model\Order\Discount\OrderDiscountLevelFacade
     */
    private $orderDiscountLevelFacade;

    /**
     * @var \App\Model\Order\Discount\OrderDiscountLevel|null
     */
    private $orderDiscountLevel;

    /**
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \App\Model\Order\Discount\OrderDiscountLevelFacade $orderDiscountLevelFacade
     */
    public function __construct(CurrencyFacade $currencyFacade, OrderDiscountLevelFacade $orderDiscountLevelFacade)
    {
        $this->currencyFacade = $currencyFacade;
        $this->orderDiscountLevelFacade = $orderDiscountLevelFacade;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->orderDiscountLevel = $options['orderDiscountLevel'];
        $domainId = $options['domainId'];
        $builder
            ->add('domainId', DomainType::class, [
                'label' => t('Doména'),
                'disabled' => $this->orderDiscountLevel !== null,
                'attr' => [
                    'class' => 'js-domain-id-select',
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
            ->add('discountPercent', PercentType::class, [
                'label' => t('Procentuální výše slevy'),
                'type' => 'integer',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Zadejte prosím výši slevy',
                    ]),
                    new Regex(['pattern' => '/^\d+$/', 'message' => 'Zadejte prosím celé číslo.']),
                    new Range([
                        'min' => 1,
                        'max' => 99,
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
            ->setRequired('orderDiscountLevel')
            ->setRequired('domainId')
            ->setAllowedTypes('orderDiscountLevel', [OrderDiscountLevel::class, 'null'])
            ->setAllowedTypes('domainId', ['int'])
            ->setDefaults([
                'data_class' => OrderDiscountLevelData::class,
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
        /** @var \App\Model\Order\Discount\OrderDiscountLevelData $orderDiscountLevelData */
        $orderDiscountLevelData = $form->getData();

        $exceptLevel = null;
        if ($this->orderDiscountLevel instanceof OrderDiscountLevel) {
            $exceptLevel = $this->orderDiscountLevel->getPriceLevelWithVat();
        }

        $allLevels = $this->orderDiscountLevelFacade->getAllLevelsOnDomainExceptLevel($orderDiscountLevelData->domainId, $exceptLevel);
        foreach ($allLevels as $level) {
            if ($priceLevel->getAmount() === $level->getAmount()) {
                $context->addViolation('Tato hladina ceny objednávky již existuje');
                return;
            }
        }
    }
}
