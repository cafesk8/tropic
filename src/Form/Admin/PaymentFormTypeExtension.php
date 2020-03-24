<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\GoPay\PaymentMethod\GoPayPaymentMethodFacade;
use App\Model\Payment\Payment;
use Shopsys\FormTypesBundle\YesNoType;
use Shopsys\FrameworkBundle\Form\Admin\Payment\PaymentFormType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class PaymentFormTypeExtension extends AbstractTypeExtension
{
    /**
     * @var \App\Model\GoPay\PaymentMethod\GoPayPaymentMethodFacade
     */
    private $goPayPaymentMethodFacade;

    /**
     * @param \App\Model\GoPay\PaymentMethod\GoPayPaymentMethodFacade $goPayPaymentMethodFacade
     */
    public function __construct(GoPayPaymentMethodFacade $goPayPaymentMethodFacade)
    {
        $this->goPayPaymentMethodFacade = $goPayPaymentMethodFacade;
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
                'label' => t('Dobírka'),
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
                        'iconTitle' => t('Tento způsob platby je skrytý systémem GoPay.'),
                    ],
                ]);
            }
        }
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     */
    private function addGiftCertificateFields(FormBuilderInterface $builder): void
    {
        $builder
            ->add('usableForGiftCertificates', YesNoType::class, [
                'required' => false,
                'label' => t('Může být použit pro dárkové poukazy'),
            ])
            ->add('activatesGiftCertificates', YesNoType::class, [
                'required' => false,
                'label' => t('Aktivovat dárkové poukazy v objednávce hned po zaplacení'),
                'attr' => [
                    'class' => 'js-payment-activates-gift-certificates',
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return PaymentFormType::class;
    }
}
