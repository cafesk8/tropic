<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\GoPay\PaymentMethod\GoPayPaymentMethodFacade;
use App\Model\Payment\Payment;
use App\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FormTypesBundle\MultidomainType;
use Shopsys\FormTypesBundle\YesNoType;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Form\Admin\Payment\PaymentFormType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class PaymentFormTypeExtension extends AbstractTypeExtension
{
    /**
     * @var \App\Model\GoPay\PaymentMethod\GoPayPaymentMethodFacade
     */
    private $goPayPaymentMethodFacade;

    private Domain $domain;

    private CurrencyFacade $currencyFacade;

    /**
     * @param \App\Model\GoPay\PaymentMethod\GoPayPaymentMethodFacade $goPayPaymentMethodFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     */
    public function __construct(GoPayPaymentMethodFacade $goPayPaymentMethodFacade, Domain $domain, CurrencyFacade $currencyFacade)
    {
        $this->goPayPaymentMethodFacade = $goPayPaymentMethodFacade;
        $this->domain = $domain;
        $this->currencyFacade = $currencyFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builderBasicInformationGroup = $builder->get('basicInformation');

        $builderBasicInformationGroup
            ->add('type', ChoiceType::class, [
                'label' => t('Type'),
                'choices' => [
                    t('Basic') => Payment::TYPE_BASIC,
                    t('GoPay') => Payment::TYPE_GOPAY,
                    t('PayPal') => Payment::TYPE_PAY_PAL,
                    t('Mall') => Payment::TYPE_MALL,
                    t('Cofidis') => Payment::TYPE_COFIDIS,
                ],
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'attr' => [
                    'class' => 'js-payment-type',
                ],
            ])
            ->add('goPayPaymentMethod', ChoiceType::class, [
                'label' => t('GoPay payment method'),
                'choices' => $this->goPayPaymentMethodFacade->getAll(),
                'choice_label' => 'name',
                'choice_value' => 'id',
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'attr' => [
                    'class' => 'js-payment-gopay-payment-method',
                ],
            ])
            ->add('externalId', TextType::class, [
                'label' => 'ID z IS',
                'required' => false,
            ])
            ->add('cashOnDelivery', YesNoType::class, [
                'required' => false,
                'label' => t('Dob??rka'),
            ])
            ->add('waitForPayment', YesNoType::class, [
                'required' => false,
                'label' => t('??ekat na platbu'),
            ]);
        $this->addGiftCertificateFields($builderBasicInformationGroup);

        if ($options['payment'] !== null) {
            /** @var \App\Model\Payment\Payment $payment */
            $payment = $options['payment'];
            if ($payment->isHiddenByGoPay()) {
                $builderBasicInformationGroup->add('hidden', YesNoType::class, [
                    'label' => t('Hidden'),
                    'required' => false,
                    'disabled' => true,
                    'attr' => [
                        'icon' => true,
                        'iconTitle' => t('Tento zp??sob platby je skryt?? syst??mem GoPay.'),
                    ],
                ]);
            }
        }

        $builderPriceGroup = $builder->get('prices');
        $this->addMinimumOrderPrice($builderPriceGroup);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     */
    private function addGiftCertificateFields(FormBuilderInterface $builder): void
    {
        $builder
            ->add('usableForGiftCertificates', YesNoType::class, [
                'required' => false,
                'label' => t('M????e b??t pou??it pro d??rkov?? poukazy'),
            ])
            ->add('activatesGiftCertificates', YesNoType::class, [
                'required' => false,
                'label' => t('Aktivovat d??rkov?? poukazy v objedn??vce hned po zaplacen??'),
                'attr' => [
                    'class' => 'js-payment-activates-gift-certificates',
                ],
            ]);
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     */
    private function addMinimumOrderPrice(FormBuilderInterface $builder): void
    {
        $minimumOrderPricesByDomainId = [];
        foreach ($this->domain->getAllIds() as $domainId) {
            $minimumOrderPricesByDomainId[$domainId] = ['currency' => $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainId)->getCode()];
        }

        $builder
            ->add('minimumOrderPrices', MultidomainType::class, [
                'required' => false,
                'entry_type' => MoneyType::class,
                'label' => t('Minim??ln?? hodnota objedn??vky'),
                'options_by_domain_id' => $minimumOrderPricesByDomainId,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        yield PaymentFormType::class;
    }
}
