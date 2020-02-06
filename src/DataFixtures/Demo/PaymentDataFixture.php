<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Payment\PaymentData;
use Shopsys\FrameworkBundle\Model\Payment\PaymentDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Payment\PaymentFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Pricing\PriceConverter;
use App\Model\Payment\Payment;

class PaymentDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    public const PAYMENT_CARD = 'payment_card';
    public const PAYMENT_CASH_ON_DELIVERY = 'payment_cash_on_delivery';
    public const PAYMENT_CASH = 'payment_cash';
    public const PAYMENT_GOPAY = Payment::TYPE_GOPAY;

    /** @var \App\Model\Payment\PaymentFacade */
    protected $paymentFacade;

    /**
     * @var \App\Model\Payment\PaymentDataFactory
     */
    protected $paymentDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\PriceConverter
     */
    protected $priceConverter;

    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    protected $currencyFacade;

    /**
     * @param \App\Model\Payment\PaymentFacade $paymentFacade
     * @param \App\Model\Payment\PaymentDataFactory $paymentDataFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Pricing\PriceConverter $priceConverter
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     */
    public function __construct(
        PaymentFacade $paymentFacade,
        PaymentDataFactoryInterface $paymentDataFactory,
        Domain $domain,
        PriceConverter $priceConverter,
        CurrencyFacade $currencyFacade
    ) {
        $this->paymentFacade = $paymentFacade;
        $this->paymentDataFactory = $paymentDataFactory;
        $this->domain = $domain;
        $this->priceConverter = $priceConverter;
        $this->currencyFacade = $currencyFacade;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $paymentData = $this->paymentDataFactory->create();
        $paymentData->type = Payment::TYPE_BASIC;
        foreach ($this->domain->getAllLocales() as $locale) {
            $paymentData->name[$locale] = t('Kreditní kartou', [], 'dataFixtures', $locale);
            $paymentData->description[$locale] = t('Rychle, levně a spolehlivě!', [], 'dataFixtures', $locale);
            $paymentData->instructions[$locale] = t('<b>Zvolili jste platbu kreditní kartou. Prosím proveďte ji do dvou pracovních dnů.</b>', [], 'dataFixtures', $locale);
        }
        $this->setPriceForAllDomainDefaultCurrencies($paymentData, Money::create('99.95'));
        $this->createPayment(self::PAYMENT_CARD, $paymentData, [
            TransportDataFixture::TRANSPORT_PERSONAL,
            TransportDataFixture::TRANSPORT_PPL,
            TransportDataFixture::TRANSPORT_PPL_DE,
            TransportDataFixture::TRANSPORT_PPL_FR,
        ]);

        $paymentData = $this->paymentDataFactory->create();
        $paymentData->type = Payment::TYPE_BASIC;
        foreach ($this->domain->getAllLocales() as $locale) {
            $paymentData->name[$locale] = t('Dobírka', [], 'dataFixtures', $locale);
        }
        $this->setPriceForAllDomainDefaultCurrencies($paymentData, Money::create('49.90'));
        $paymentData->cashOnDelivery = true;
        $this->createPayment(self::PAYMENT_CASH_ON_DELIVERY, $paymentData, [TransportDataFixture::TRANSPORT_CZECH_POST]);

        $paymentData = $this->paymentDataFactory->create();
        $paymentData->type = Payment::TYPE_BASIC;
        foreach ($this->domain->getAllLocales() as $locale) {
            $paymentData->name[$locale] = t('Hotově', [], 'dataFixtures', $locale);
        }
        $paymentData->czkRounding = true;
        $this->setPriceForAllDomainDefaultCurrencies($paymentData, Money::zero());
        $this->createPayment(self::PAYMENT_CASH, $paymentData, [TransportDataFixture::TRANSPORT_PERSONAL]);

        $paymentData = $this->paymentDataFactory->create();
        $paymentData->type = self::PAYMENT_GOPAY;
        foreach ($this->domain->getAllLocales() as $locale) {
            $paymentData->name[$locale] = t('GoPay - Platba kartou', [], 'dataFixtures', $locale);
        }
        $paymentData->czkRounding = false;
        $this->setPriceForAllDomainDefaultCurrencies($paymentData, Money::zero());
        $paymentData->goPayPaymentMethod = $this->getReference(GoPayDataFixture::PAYMENT_CARD_METHOD);
        foreach ($this->domain->getAllLocales() as $locale) {
            $paymentData->instructions[$locale] = t('<b>Zvolili jste platbu GoPay, bude Vám zobrazena platební brána.</b>', [], 'dataFixtures', $locale);
        }
        $paymentData->enabled[Domain::FIRST_DOMAIN_ID] = true;
        $paymentData->hidden = false;
        $this->createPayment(self::PAYMENT_GOPAY, $paymentData, [
            TransportDataFixture::TRANSPORT_PERSONAL,
            TransportDataFixture::TRANSPORT_PPL,
            TransportDataFixture::TRANSPORT_PPL_DE,
            TransportDataFixture::TRANSPORT_PPL_FR,
        ]);

        $paymentData = $this->paymentDataFactory->create();
        $paymentData->type = Payment::TYPE_PAY_PAL;
        foreach ($this->domain->getAllLocales() as $locale) {
            $paymentData->name[$locale] = t('PayPal', [], 'dataFixtures', $locale);
        }
        $paymentData->czkRounding = false;
        $this->setPriceForAllDomainDefaultCurrencies($paymentData, Money::zero());
        foreach ($this->domain->getAllLocales() as $locale) {
            $paymentData->instructions[$locale] = t('<b>Zvolili jste platbu PayPal, budete přesměrováni na platební bránu.</b>', [], 'dataFixtures', $locale);
        }
        $paymentData->enabled[Domain::FIRST_DOMAIN_ID] = true;
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
        foreach ($this->domain->getAllLocales() as $locale) {
            $paymentData->name[$locale] = t('Mall', [], 'dataFixtures', $locale);
        }
        $paymentData->czkRounding = false;
        $this->setPriceForAllDomainDefaultCurrencies($paymentData, Money::zero());
        foreach ($this->domain->getAllLocales() as $locale) {
            $paymentData->description[$locale] = t('Platba provedena u mall.cz', [], 'dataFixtures', $locale);
        }
        $paymentData->enabled[Domain::FIRST_DOMAIN_ID] = true;
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
     * @param \App\Model\Payment\PaymentData $paymentData
     * @param array $transportsReferenceNames
     */
    protected function createPayment(
        $referenceName,
        PaymentData $paymentData,
        array $transportsReferenceNames
    ) {
        $paymentData->transports = [];
        foreach ($transportsReferenceNames as $transportReferenceName) {
            /** @var \App\Model\Transport\Transport $transport */
            $transport = $this->getReference($transportReferenceName);
            $paymentData->transports[] = $transport;
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
            GoPayDataFixture::class,
            SettingValueDataFixture::class,
        ];
    }

    /**
     * @param \App\Model\Payment\PaymentData $paymentData
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $price
     */
    protected function setPriceForAllDomainDefaultCurrencies(PaymentData $paymentData, Money $price): void
    {
        foreach ($this->domain->getAllIncludingDomainConfigsWithoutDataCreated() as $domain) {
            $price = $this->priceConverter->convertPriceWithoutVatToPriceInDomainDefaultCurrency($price, $domain->getId());

            /** @var \Shopsys\FrameworkBundle\Model\Pricing\Vat\Vat $vat */
            $vat = $this->getReferenceForDomain(VatDataFixture::VAT_ZERO, $domain->getId());
            $paymentData->pricesIndexedByDomainId[$domain->getId()] = $price;
            $paymentData->vatsIndexedByDomainId[$domain->getId()] = $vat;
        }
    }
}
