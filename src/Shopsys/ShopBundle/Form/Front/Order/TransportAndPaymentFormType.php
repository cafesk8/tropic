<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Form\Front\Order;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Form\SingleCheckboxChoiceType;
use Shopsys\FrameworkBundle\Model\Country\Country;
use Shopsys\FrameworkBundle\Model\Order\OrderData;
use Shopsys\FrameworkBundle\Model\Payment\Payment;
use Shopsys\FrameworkBundle\Model\Payment\PaymentFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Transport\Transport;
use Shopsys\FrameworkBundle\Model\Transport\TransportFacade;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Model\Cart\CartFacade;
use Shopsys\ShopBundle\Model\Country\CountryFacade;
use Shopsys\ShopBundle\Model\GoPay\BankSwift\GoPayBankSwiftFacade;
use Shopsys\ShopBundle\Model\Store\StoreIdToEntityTransformer;
use Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlaceIdToEntityTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class TransportAndPaymentFormType extends AbstractType
{
    /**
     * @var \Shopsys\ShopBundle\Model\Transport\TransportFacade
     */
    private $transportFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Payment\PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\GoPay\BankSwift\GoPayBankSwiftFacade
     */
    private $goPayBankSwiftFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlaceIdToEntityTransformer
     */
    private $pickupPlaceIdToEntityTransformer;

    /**
     * @var \Shopsys\ShopBundle\Model\Store\StoreIdToEntityTransformer
     */
    private $storeIdToEntityTransformer;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\ShopBundle\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Cart\CartFacade
     */
    private $cartFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\TransportFacade $transportFacade
     * @param \Shopsys\FrameworkBundle\Model\Payment\PaymentFacade $paymentFacade
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \Shopsys\ShopBundle\Model\GoPay\BankSwift\GoPayBankSwiftFacade $goPayBankSwiftFacade
     * @param \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlaceIdToEntityTransformer $pickupPlaceIdToEntityTransformer
     * @param \Shopsys\ShopBundle\Model\Store\StoreIdToEntityTransformer $storeIdToEntityTransformer
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\ShopBundle\Model\Country\CountryFacade $countryFacade
     * @param \Shopsys\ShopBundle\Model\Cart\CartFacade $cartFacade
     */
    public function __construct(
        TransportFacade $transportFacade,
        PaymentFacade $paymentFacade,
        CurrencyFacade $currencyFacade,
        GoPayBankSwiftFacade $goPayBankSwiftFacade,
        PickupPlaceIdToEntityTransformer $pickupPlaceIdToEntityTransformer,
        StoreIdToEntityTransformer $storeIdToEntityTransformer,
        Domain $domain,
        CountryFacade $countryFacade,
        CartFacade $cartFacade
    ) {
        $this->transportFacade = $transportFacade;
        $this->paymentFacade = $paymentFacade;
        $this->currencyFacade = $currencyFacade;
        $this->goPayBankSwiftFacade = $goPayBankSwiftFacade;
        $this->pickupPlaceIdToEntityTransformer = $pickupPlaceIdToEntityTransformer;
        $this->storeIdToEntityTransformer = $storeIdToEntityTransformer;
        $this->domain = $domain;
        $this->countryFacade = $countryFacade;
        $this->cartFacade = $cartFacade;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $country = $this->countryFacade->getHackedCountry();

        $payments = $this->paymentFacade->getVisibleByDomainId($options['domain_id']);

        $showEmailTransportInCart = $this->cartFacade->showEmailTransportInCart();
        $transports = $this->transportFacade->getVisibleByDomainIdAndCountryAndTransportEmailType(
            $options['domain_id'],
            $payments,
            $country,
            $showEmailTransportInCart
        );

        $currency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($options['domain_id']);

        if (DomainHelper::isGermanDomain($this->domain)) {
            $countries = $this->countryFacade->getAllEnabledOnCurrentDomain();

            $builder->add('country', ChoiceType::class, [
                'choices' => $countries,
                'data' => $country,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'constraints' => [
                    new Constraints\NotBlank(['message' => 'Please choose country']),
                ],
                'attr' => [
                    'class' => 'js-transport-country',
                ],
            ]);
        }

        $builder
            ->add('transport', SingleCheckboxChoiceType::class, [
                'choices' => $transports,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'constraints' => [
                    new Constraints\NotNull(['message' => 'Please choose shipping type']),
                ],
                'invalid_message' => 'Please choose shipping type',
            ])
            ->add('payment', SingleCheckboxChoiceType::class, [
                'choices' => $payments,
                'choice_label' => 'name',
                'choice_value' => 'id',
                'constraints' => [
                    new Constraints\NotNull(['message' => 'Please choose payment type']),
                ],
                'invalid_message' => 'Please choose payment type',
            ])
            ->add('goPayBankSwift', SingleCheckboxChoiceType::class, [
                'choices' => $this->goPayBankSwiftFacade->getAllByCurrencyId($currency->getId()),
                'choice_label' => 'name',
                'choice_value' => 'id',
            ])
            ->add(
                $builder
                    ->create('pickupPlace', HiddenType::class)
                    ->addModelTransformer($this->pickupPlaceIdToEntityTransformer)
            )
            ->add(
                $builder
                    ->create('store', HiddenType::class)
                    ->addModelTransformer($this->storeIdToEntityTransformer)
            )
            ->add('save', SubmitType::class);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('domain_id')
            ->setAllowedTypes('domain_id', 'int')
            ->setRequired('country')
            ->setAllowedTypes('country', [Country::class, 'null'])
            ->setDefaults([
                'attr' => ['novalidate' => 'novalidate'],
                'constraints' => [
                    new Constraints\Callback([$this, 'validateTransportPaymentRelation']),
                ],
            ]);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\OrderData $orderData
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public function validateTransportPaymentRelation(OrderData $orderData, ExecutionContextInterface $context)
    {
        $payment = $orderData->payment;
        $transport = $orderData->transport;

        $relationExists = false;
        if ($payment instanceof Payment && $transport instanceof Transport) {
            if ($payment->getTransports()->contains($transport)) {
                $relationExists = true;
            }
        }

        if (!$relationExists) {
            $context->addViolation('Please choose a valid combination of transport and payment');
        }

        if ($transport instanceof Transport && $transport->isPickupPlaceType() && $this->isPickupPlaceAndStoreNull($orderData)) {
            $context->addViolation('Vyberte prosím pobočku');
        }
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\OrderData $orderData
     * @return bool
     */
    private function isPickupPlaceAndStoreNull(OrderData $orderData): bool
    {
        return $orderData->pickupPlace === null && $orderData->store === null;
    }
}
