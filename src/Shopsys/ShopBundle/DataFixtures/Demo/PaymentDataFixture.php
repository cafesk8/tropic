<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Payment\PaymentData;
use Shopsys\FrameworkBundle\Model\Payment\PaymentDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Payment\PaymentFacade;
use Shopsys\ShopBundle\Model\Payment\Payment;

class PaymentDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    public const PAYMENT_CARD = 'payment_card';
    public const PAYMENT_CASH_ON_DELIVERY = 'payment_cash_on_delivery';
    public const PAYMENT_CASH = 'payment_cash';

    /** @var \Shopsys\FrameworkBundle\Model\Payment\PaymentFacade */
    protected $paymentFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Payment\PaymentDataFactoryInterface
     */
    protected $paymentDataFactory;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Payment\PaymentFacade $paymentFacade
     * @param \Shopsys\FrameworkBundle\Model\Payment\PaymentDataFactoryInterface $paymentDataFactory
     */
    public function __construct(
        PaymentFacade $paymentFacade,
        PaymentDataFactoryInterface $paymentDataFactory
    ) {
        $this->paymentFacade = $paymentFacade;
        $this->paymentDataFactory = $paymentDataFactory;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var \Shopsys\ShopBundle\Model\Payment\PaymentData $paymentData */
        $paymentData = $this->paymentDataFactory->create();
        $paymentData->type = Payment::TYPE_BASIC;
        $paymentData->name = [
            'cs' => 'Kreditní kartou',
            'sk' => 'Kreditní kartou',
            'de' => 'Credit card',
        ];
        $paymentData->pricesByCurrencyId = [
            $this->getReference(CurrencyDataFixture::CURRENCY_CZK)->getId() => Money::create('99.95'),
            $this->getReference(CurrencyDataFixture::CURRENCY_EUR)->getId() => Money::create('2.95'),
        ];
        $paymentData->description = [
            'cs' => 'Rychle, levně a spolehlivě!',
            'sk' => 'Rychle, levně a spolehlivě!',
            'de' => 'Quick, cheap and reliable!',
        ];
        $paymentData->instructions = [
            'cs' => '<b>Zvolili jste platbu kreditní kartou. Prosím proveďte ji do dvou pracovních dnů.</b>',
            'sk' => '<b>Zvolili jste platbu kreditní kartou. Prosím proveďte ji do dvou pracovních dnů.</b>',
            'de' => '<b>You have chosen payment by credit card. Please finish it in two business days.</b>',
        ];
        $paymentData->vat = $this->getReference(VatDataFixture::VAT_ZERO);
        $this->createPayment(self::PAYMENT_CARD, $paymentData, [
            TransportDataFixture::TRANSPORT_PERSONAL,
            TransportDataFixture::TRANSPORT_PPL,
            TransportDataFixture::TRANSPORT_PPL_DE,
            TransportDataFixture::TRANSPORT_PPL_FR,
        ]);

        $paymentData = $this->paymentDataFactory->create();
        $paymentData->type = Payment::TYPE_BASIC;
        $paymentData->name = [
            'cs' => 'Dobírka',
            'sk' => 'Dobírka',
            'de' => 'Cash on delivery',
        ];
        $paymentData->pricesByCurrencyId = [
            $this->getReference(CurrencyDataFixture::CURRENCY_CZK)->getId() => Money::create('49.90'),
            $this->getReference(CurrencyDataFixture::CURRENCY_EUR)->getId() => Money::create('1.95'),
        ];
        $paymentData->vat = $this->getReference(VatDataFixture::VAT_HIGH);
        $paymentData->cashOnDelivery = true;
        $this->createPayment(self::PAYMENT_CASH_ON_DELIVERY, $paymentData, [TransportDataFixture::TRANSPORT_CZECH_POST]);

        $paymentData = $this->paymentDataFactory->create();
        $paymentData->type = Payment::TYPE_BASIC;
        $paymentData->name = [
            'cs' => 'Hotově',
            'sk' => 'Hotově',
            'de' => 'Cash',
        ];
        $paymentData->czkRounding = true;
        $paymentData->pricesByCurrencyId = [
            $this->getReference(CurrencyDataFixture::CURRENCY_CZK)->getId() => Money::zero(),
            $this->getReference(CurrencyDataFixture::CURRENCY_EUR)->getId() => Money::zero(),
        ];
        $paymentData->vat = $this->getReference(VatDataFixture::VAT_HIGH);
        $this->createPayment(self::PAYMENT_CASH, $paymentData, [TransportDataFixture::TRANSPORT_PERSONAL]);

        $paymentData = $this->paymentDataFactory->create();
        $paymentData->type = Payment::TYPE_GOPAY;
        $paymentData->name = [
            'cs' => 'GoPay - Platba kartou',
            'sk' => 'GoPay - Platba kartou',
            'de' => 'GoPay - Pay by card',
        ];
        $paymentData->czkRounding = false;
        $paymentData->pricesByCurrencyId = [
            $this->getReference(CurrencyDataFixture::CURRENCY_CZK)->getId() => Money::zero(),
            $this->getReference(CurrencyDataFixture::CURRENCY_EUR)->getId() => Money::zero(),
        ];
        $paymentData->goPayPaymentMethod = $this->getReference(GoPayDataFixture::PAYMENT_CARD_METHOD);
        $paymentData->prices = [
            $this->getReference(CurrencyDataFixture::CURRENCY_CZK)->getId() => Money::zero(),
            $this->getReference(CurrencyDataFixture::CURRENCY_EUR)->getId() => Money::zero(),
        ];
        $paymentData->description = [
            'cs' => '',
            'sk' => '',
            'de' => '',
        ];
        $paymentData->instructions = [
            'cs' => '<b>Zvolili jste platbu GoPay, bude Vám zobrazena platební brána.</b>',
            'sk' => '',
            'de' => '',
        ];
        $paymentData->vat = $this->getReference(VatDataFixture::VAT_HIGH);
        $paymentData->domains = [Domain::FIRST_DOMAIN_ID];
        $paymentData->hidden = false;
        $this->createPayment(Payment::TYPE_GOPAY, $paymentData, [
            TransportDataFixture::TRANSPORT_PERSONAL,
            TransportDataFixture::TRANSPORT_PPL,
            TransportDataFixture::TRANSPORT_PPL_DE,
            TransportDataFixture::TRANSPORT_PPL_FR,
        ]);

        $paymentData = $this->paymentDataFactory->create();
        $paymentData->type = Payment::TYPE_PAY_PAL;
        $paymentData->name = [
            'cs' => 'PayPal',
            'sk' => 'PayPal',
            'de' => 'PayPal',
        ];
        $paymentData->czkRounding = false;
        $paymentData->pricesByCurrencyId = [
            $this->getReference(CurrencyDataFixture::CURRENCY_CZK)->getId() => Money::zero(),
            $this->getReference(CurrencyDataFixture::CURRENCY_EUR)->getId() => Money::zero(),
        ];
        $paymentData->prices = [
            $this->getReference(CurrencyDataFixture::CURRENCY_CZK)->getId() => Money::zero(),
            $this->getReference(CurrencyDataFixture::CURRENCY_EUR)->getId() => Money::zero(),
        ];
        $paymentData->description = [
            'cs' => '',
            'sk' => '',
            'de' => '',
        ];
        $paymentData->instructions = [
            'cs' => '<b>Zvolili jste platbu PayPal, budete přesměrováni na platební bránu.</b>',
            'sk' => '',
            'de' => '',
        ];
        $paymentData->vat = $this->getReference(VatDataFixture::VAT_HIGH);
        $paymentData->domains = [Domain::FIRST_DOMAIN_ID];
        $paymentData->hidden = false;
        $this->createPayment(Payment::TYPE_PAY_PAL, $paymentData, [
            TransportDataFixture::TRANSPORT_PERSONAL,
            TransportDataFixture::TRANSPORT_PPL,
            TransportDataFixture::TRANSPORT_CZECH_POST,
            TransportDataFixture::TRANSPORT_PPL_DE,
            TransportDataFixture::TRANSPORT_PPL_FR,
        ]);

        $paymentData = $this->paymentDataFactory->create();
        $paymentData->type = Payment::TYPE_MALL;
        $paymentData->name = [
            'cs' => 'Mall',
            'sk' => 'Mall',
            'de' => 'Mall',
        ];
        $paymentData->czkRounding = false;
        $paymentData->pricesByCurrencyId = [
            $this->getReference(CurrencyDataFixture::CURRENCY_CZK)->getId() => Money::zero(),
            $this->getReference(CurrencyDataFixture::CURRENCY_EUR)->getId() => Money::zero(),
        ];
        $paymentData->prices = [
            $this->getReference(CurrencyDataFixture::CURRENCY_CZK)->getId() => Money::zero(),
            $this->getReference(CurrencyDataFixture::CURRENCY_EUR)->getId() => Money::zero(),
        ];
        $paymentData->description = [
            'cs' => 'Platba provedena u mall.cz',
            'sk' => '',
            'de' => '',
        ];
        $paymentData->instructions = [
            'cs' => '',
            'sk' => '',
            'de' => '',
        ];
        $paymentData->vat = $this->getReference(VatDataFixture::VAT_HIGH);
        $paymentData->domains = [Domain::FIRST_DOMAIN_ID];
        $paymentData->hidden = true;
        $this->createPayment(Payment::TYPE_PAY_PAL, $paymentData, [
            TransportDataFixture::TRANSPORT_PERSONAL,
            TransportDataFixture::TRANSPORT_PPL,
            TransportDataFixture::TRANSPORT_CZECH_POST,
            TransportDataFixture::TRANSPORT_PPL_DE,
            TransportDataFixture::TRANSPORT_PPL_FR,
        ]);
    }

    /**
     * @param string $referenceName
     * @param \Shopsys\FrameworkBundle\Model\Payment\PaymentData $paymentData
     * @param array $transportsReferenceNames
     */
    protected function createPayment(
        $referenceName,
        PaymentData $paymentData,
        array $transportsReferenceNames
    ) {
        $paymentData->transports = [];
        foreach ($transportsReferenceNames as $transportReferenceName) {
            $paymentData->transports[] = $this->getReference($transportReferenceName);
        }

        $payment = $this->paymentFacade->create($paymentData);
        $this->addReference($referenceName, $payment);
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            TransportDataFixture::class,
            VatDataFixture::class,
            CurrencyDataFixture::class,
        ];
    }
}
