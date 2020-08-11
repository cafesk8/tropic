<?php

declare(strict_types=1);

namespace App\Form\Front\Order;

use App\Component\Domain\DomainHelper;
use App\Model\Cart\CartFacade;
use App\Model\Country\CountryFacade;
use App\Model\GoPay\BankSwift\GoPayBankSwiftFacade;
use App\Model\Order\Preview\OrderPreviewFactory;
use App\Model\Store\StoreIdToEntityTransformer;
use App\Model\Transport\PickupPlace\PickupPlaceIdToEntityTransformer;
use App\Model\Transport\Transport;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Form\SingleCheckboxChoiceType;
use Shopsys\FrameworkBundle\Model\Country\Country;
use Shopsys\FrameworkBundle\Model\Order\OrderData;
use Shopsys\FrameworkBundle\Model\Payment\Payment;
use Shopsys\FrameworkBundle\Model\Payment\PaymentFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Transport\Transport as BaseTransport;
use Shopsys\FrameworkBundle\Model\Transport\TransportFacade;
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
     * @var \App\Model\Transport\TransportFacade
     */
    private $transportFacade;

    /**
     * @var \App\Model\Payment\PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @var \App\Model\GoPay\BankSwift\GoPayBankSwiftFacade
     */
    private $goPayBankSwiftFacade;

    /**
     * @var \App\Model\Transport\PickupPlace\PickupPlaceIdToEntityTransformer
     */
    private $pickupPlaceIdToEntityTransformer;

    /**
     * @var \App\Model\Store\StoreIdToEntityTransformer
     */
    private $storeIdToEntityTransformer;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @var \App\Model\Cart\CartFacade
     */
    private $cartFacade;

    private OrderPreviewFactory $orderPreviewFactory;

    /**
     * @param \App\Model\Transport\TransportFacade $transportFacade
     * @param \App\Model\Payment\PaymentFacade $paymentFacade
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \App\Model\GoPay\BankSwift\GoPayBankSwiftFacade $goPayBankSwiftFacade
     * @param \App\Model\Transport\PickupPlace\PickupPlaceIdToEntityTransformer $pickupPlaceIdToEntityTransformer
     * @param \App\Model\Store\StoreIdToEntityTransformer $storeIdToEntityTransformer
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Country\CountryFacade $countryFacade
     * @param \App\Model\Cart\CartFacade $cartFacade
     * @param \App\Model\Order\Preview\OrderPreviewFactory $orderPreviewFactory
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
        CartFacade $cartFacade,
        OrderPreviewFactory $orderPreviewFactory
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
        $this->orderPreviewFactory = $orderPreviewFactory;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $country = $options['country'] ?? $this->countryFacade->getHackedCountry();
        $orderPreview = $this->orderPreviewFactory->createForCurrentUser();
        $orderPrice = $orderPreview->getTotalPrice()->getPriceWithVat();
        $showOnlyGiftCertificatePaymentsInCart = $this->cartFacade->showOnlyGiftCertificatePaymentsInCart();
        $payments = $this->paymentFacade->getVisibleByDomainIdAndGiftCertificateUsabilityAndPrice($options['domain_id'], $showOnlyGiftCertificatePaymentsInCart, $orderPrice);
        $showEmailTransportInCart = $this->cartFacade->showEmailTransportInCart();

        $oversizedTransportRequired = $this->cartFacade->isOversizedTransportRequired();
        $bulkyTransportRequired = $this->cartFacade->isBulkyTransportRequired();

        $transports = $this->transportFacade->getVisibleByDomainIdAndCountryAndTransportEmailType(
            $options['domain_id'],
            $payments,
            $country,
            $showEmailTransportInCart,
            $oversizedTransportRequired,
            $bulkyTransportRequired,
        );

        $currency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($options['domain_id']);

        if (DomainHelper::isEnglishDomain($this->domain)) {
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
                    new Constraints\Callback([$this, 'validateBulkyTransportRequirement']),
                    new Constraints\Callback([$this, 'validateOversizedTransportRequirement']),
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
            ->setRequired('order_price')
            ->setAllowedTypes('order_price', [Money::class, 'null'])
            ->setDefaults([
                'attr' => ['novalidate' => 'novalidate'],
                'constraints' => [
                    new Constraints\Callback([$this, 'validateTransportPaymentRelation']),
                ],
            ]);
    }

    /**
     * @param \App\Model\Order\OrderData $orderData
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public function validateTransportPaymentRelation(OrderData $orderData, ExecutionContextInterface $context)
    {
        $payment = $orderData->payment;
        $transport = $orderData->transport;

        $relationExists = false;
        if ($payment instanceof Payment && $transport instanceof BaseTransport) {
            if (in_array($transport, $payment->getTransports(), true)) {
                $relationExists = true;
            }
        }

        if (!$relationExists) {
            $context->addViolation('Please choose a valid combination of transport and payment');
        }

        if ($transport instanceof BaseTransport && $transport->isPickupPlaceType() && $this->isPickupPlaceAndStoreNull($orderData)) {
            $context->addViolation('Vyberte prosím pobočku');
        }
    }

    /**
     * @param \App\Model\Order\OrderData $orderData
     * @return bool
     */
    private function isPickupPlaceAndStoreNull(OrderData $orderData): bool
    {
        return $orderData->pickupPlace === null && $orderData->store === null;
    }

    /**
     * @param \App\Model\Transport\Transport|null $transport
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public function validateBulkyTransportRequirement(?Transport $transport, ExecutionContextInterface $context): void
    {
        $bulkyTransportRequired = $this->cartFacade->isBulkyTransportRequired();

        if (!$bulkyTransportRequired || $transport === null || $transport->isBulkyAllowed()) {
            return;
        }

        $cart = $this->cartFacade->findCartOfCurrentCustomerUser();

        if ($cart === null) {
            return;
        }

        foreach ($cart->getItems() as $item) {
            if ($item->getProduct()->isBulky()) {
                $context->addViolation(t('Máte v košíku objemný produkt, ale zvolený způsob dopravy neumožňuje objemné produkty dopravovat'));
                break;
            }
        }
    }

    /**
     * @param \App\Model\Transport\Transport|null $transport
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public function validateOversizedTransportRequirement(?Transport $transport, ExecutionContextInterface $context): void
    {
        $oversizedTransportRequired = $this->cartFacade->isOversizedTransportRequired();

        if (!$oversizedTransportRequired || $transport === null || $transport->isOversizedAllowed()) {
            return;
        }

        $cart = $this->cartFacade->findCartOfCurrentCustomerUser();

        if ($cart === null) {
            return;
        }

        foreach ($cart->getItems() as $item) {
            if ($item->getProduct()->isOversized()) {
                $context->addViolation(t('Máte v košíku nadrozměrný produkt, ale zvolený způsob dopravy neumožňuje nadrozměrné produkty dopravovat'));
                break;
            }
        }
    }
}
