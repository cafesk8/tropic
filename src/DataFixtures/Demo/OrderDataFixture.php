<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Model\Order\Item\QuantifiedProduct;
use App\Model\Order\Order;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Generator;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserRepository;
use Shopsys\FrameworkBundle\Model\Order\OrderData;
use Shopsys\FrameworkBundle\Model\Order\OrderDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\OrderFacade;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreviewFactory;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;

class OrderDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    public const ORDER_PREFIX = 'order_';

    /**
     * @var \App\Model\Customer\User\CustomerUserRepository
     */
    protected $customerUserRepository;

    /**
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * @var \App\Model\Order\OrderFacade
     */
    protected $orderFacade;

    /**
     * @var \App\Model\Order\Preview\OrderPreviewFactory
     */
    protected $orderPreviewFactory;

    /**
     * @var \App\Model\Order\OrderDataFactory
     */
    protected $orderDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    protected $currencyFacade;

    /**
     * @param \App\Model\Customer\User\CustomerUserRepository $customerUserRepository
     * @param \Faker\Generator $faker
     * @param \App\Model\Order\OrderFacade $orderFacade
     * @param \App\Model\Order\Preview\OrderPreviewFactory $orderPreviewFactory
     * @param \App\Model\Order\OrderDataFactory $orderDataFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     */
    public function __construct(
        CustomerUserRepository $customerUserRepository,
        Generator $faker,
        OrderFacade $orderFacade,
        OrderPreviewFactory $orderPreviewFactory,
        OrderDataFactoryInterface $orderDataFactory,
        Domain $domain,
        CurrencyFacade $currencyFacade
    ) {
        $this->customerUserRepository = $customerUserRepository;
        $this->faker = $faker;
        $this->orderFacade = $orderFacade;
        $this->orderPreviewFactory = $orderPreviewFactory;
        $this->orderDataFactory = $orderDataFactory;
        $this->domain = $domain;
        $this->currencyFacade = $currencyFacade;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->domain->getAll() as $domainConfig) {
            $domainId = $domainConfig->getId();
            if ($domainId === Domain::SECOND_DOMAIN_ID) {
                $this->loadDistinct($domainId);
            } else {
                $this->loadDefault($domainId);
            }
        }
    }

    /**
     * @param int $domainId
     */
    protected function loadDefault(int $domainId): void
    {
        $domainDefaultCurrency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainId);

        $customerUser = $this->customerUserRepository->findCustomerUserByEmailAndDomain('no-reply@shopsys.com', $domainId);
        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_PERSONAL);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_GOPAY);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_DONE);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->street = 'fakt. První 1';
        $orderData->city = 'fakt. Ostrava';
        $orderData->postcode = '71200';
        $orderData->deliveryFirstName = 'Jiří';
        $orderData->deliveryLastName = 'Ševčík';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->deliveryTelephone = '+420369554147';
        $orderData->deliveryStreet = 'První 1';
        $orderData->deliveryCity = 'Ostrava';
        $orderData->deliveryPostcode = '71200';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->deliveryAddressSameAsBillingAddress = false;
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');

        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '9' => 2,
                ProductDataFixture::PRODUCT_PREFIX . '10' => 3,
            ],
            $customerUser
        );

        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_PERSONAL);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CARD);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_NEW);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $orderData->street = 'fakt. Druhá 2';
        $orderData->city = 'fatk. Ostrava';
        $orderData->postcode = '71300';
        $orderData->deliveryFirstName = 'Iva';
        $orderData->deliveryLastName = 'Jačková';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->deliveryTelephone = '+420367852147';
        $orderData->deliveryStreet = 'Druhá 2';
        $orderData->deliveryCity = 'Ostrava';
        $orderData->deliveryPostcode = '71300';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $orderData->deliveryAddressSameAsBillingAddress = false;
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $orderData->createdAsAdministrator = $this->getReference(AdministratorDataFixture::ADMINISTRATOR);
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '18' => 2,
                ProductDataFixture::PRODUCT_PREFIX . '19' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '20' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '15' => 5,
            ],
            $customerUser
        );

        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_CZECH_POST);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CASH_ON_DELIVERY);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_NEW);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->street = 'fakt. Třetí 3';
        $orderData->city = 'fakt. Ostrava';
        $orderData->postcode = '71200';
        $orderData->deliveryFirstName = 'Jan';
        $orderData->deliveryLastName = 'Adamovský';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->deliveryTelephone = '+420725852147';
        $orderData->deliveryStreet = 'Třetí 3';
        $orderData->deliveryCity = 'Ostrava';
        $orderData->deliveryPostcode = '71200';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->deliveryAddressSameAsBillingAddress = false;
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '4' => 6,
                ProductDataFixture::PRODUCT_PREFIX . '11' => 1,
            ],
            $customerUser
        );

        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_PPL);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CARD);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_IN_PROGRESS);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $orderData->street = 'fakt. Čtvrtá 4';
        $orderData->city = 'fakt. Ostrava';
        $orderData->postcode = '70030';
        $orderData->deliveryFirstName = 'Iveta';
        $orderData->deliveryLastName = 'Prvá';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->deliveryTelephone = '+420606952147';
        $orderData->deliveryStreet = 'Čtvrtá 4';
        $orderData->deliveryCity = 'Ostrava';
        $orderData->deliveryPostcode = '70030';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $orderData->deliveryAddressSameAsBillingAddress = false;
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '1' => 1,
            ],
            $customerUser
        );

        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_PERSONAL);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CASH);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_DONE);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->street = 'fakt. Pátá 55';
        $orderData->city = 'Ostrava';
        $orderData->postcode = '71200';
        $orderData->deliveryFirstName = 'Jana';
        $orderData->deliveryLastName = 'Janíčková';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->deliveryTelephone = '+420739852148';
        $orderData->deliveryStreet = 'Pátá 55';
        $orderData->deliveryCity = 'Ostrava';
        $orderData->deliveryPostcode = '71200';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->deliveryAddressSameAsBillingAddress = true;
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $orderData->createdAsAdministrator = $this->getReference(AdministratorDataFixture::SUPERADMINISTRATOR);
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '2' => 8,
                ProductDataFixture::PRODUCT_PREFIX . '3' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '1' => 2,
            ],
            $customerUser
        );

        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_PPL);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CARD);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_NEW);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->street = 'Šestá 39';
        $orderData->city = 'Pardubice';
        $orderData->postcode = '58941';
        $orderData->deliveryFirstName = 'Dominik';
        $orderData->deliveryLastName = 'Hašek';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->deliveryTelephone = '+420721852152';
        $orderData->deliveryStreet = 'Šestá 39';
        $orderData->deliveryCity = 'Pardubice';
        $orderData->deliveryPostcode = '58941';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $orderData->deliveryAddressSameAsBillingAddress = true;
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '13' => 2,
                ProductDataFixture::PRODUCT_PREFIX . '14' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '15' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '16' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '17' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '18' => 1,
            ],
            $customerUser
        );

        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_PERSONAL);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CASH);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_CANCELED);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->street = 'Sedmá 1488';
        $orderData->city = 'Opava';
        $orderData->postcode = '85741';
        $orderData->deliveryFirstName = 'Jiří';
        $orderData->deliveryLastName = 'Sovák';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->deliveryTelephone = '+420755872155';
        $orderData->deliveryStreet = 'Sedmá 1488';
        $orderData->deliveryCity = 'Opava';
        $orderData->deliveryPostcode = '85741';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->deliveryAddressSameAsBillingAddress = true;
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '7' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '8' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '12' => 2,
            ]
        );

        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_CZECH_POST);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CASH_ON_DELIVERY);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_DONE);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $orderData->street = 'Osmá 1';
        $orderData->city = 'Praha';
        $orderData->postcode = '30258';
        $orderData->deliveryFirstName = 'Josef';
        $orderData->deliveryLastName = 'Somr';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->deliveryTelephone = '+420369852147';
        $orderData->deliveryStreet = 'Osmá 1';
        $orderData->deliveryCity = 'Praha';
        $orderData->deliveryPostcode = '30258';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $orderData->deliveryAddressSameAsBillingAddress = true;
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $orderData->createdAsAdministrator = $this->getReference(AdministratorDataFixture::SUPERADMINISTRATOR);
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '1' => 6,
                ProductDataFixture::PRODUCT_PREFIX . '2' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '12' => 1,
            ]
        );

        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_PERSONAL);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CASH);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_CANCELED);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->street = 'fakt. Desátá 10';
        $orderData->city = 'fakt. Plzeň';
        $orderData->deliveryPostcode = '30010';
        $orderData->deliveryFirstName = 'Ivan';
        $orderData->deliveryLastName = 'Horník';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->deliveryTelephone = '+420755496328';
        $orderData->deliveryStreet = 'Desátá 10';
        $orderData->deliveryCity = 'Plzeň';
        $orderData->deliveryPostcode = '30010';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->deliveryAddressSameAsBillingAddress = false;
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '9' => 3,
                ProductDataFixture::PRODUCT_PREFIX . '13' => 2,
            ]
        );

        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_PPL);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CARD);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_NEW);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->street = 'Cihelní 5';
        $orderData->city = 'Liberec';
        $orderData->postcode = '65421';
        $orderData->deliveryFirstName = 'Adam';
        $orderData->deliveryLastName = 'Bořič';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->deliveryTelephone = '+420987654321';
        $orderData->deliveryStreet = 'Cihelní 5';
        $orderData->deliveryCity = 'Liberec';
        $orderData->deliveryPostcode = '65421';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->deliveryAddressSameAsBillingAddress = true;
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '3' => 1,
            ]
        );

        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_PERSONAL);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CASH);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_IN_PROGRESS);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $orderData->deliveryFirstName = 'Evžen';
        $orderData->deliveryLastName = 'Farný';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->deliveryTelephone = '+420456789123';
        $orderData->deliveryStreet = 'Gagarinova 333';
        $orderData->deliveryCity = 'Hodonín';
        $orderData->deliveryPostcode = '69501';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $orderData->deliveryAddressSameAsBillingAddress = false;
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '1' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '2' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '3' => 1,
            ]
        );

        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_PERSONAL);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CASH);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_DONE);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->deliveryFirstName = 'Ivana';
        $orderData->deliveryLastName = 'Janečková';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->deliveryTelephone = '+420369852147';
        $orderData->deliveryStreet = 'Kalužní 88';
        $orderData->deliveryCity = 'Lednice';
        $orderData->deliveryPostcode = '69144';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->deliveryAddressSameAsBillingAddress = false;
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '4' => 2,
                ProductDataFixture::PRODUCT_PREFIX . '3' => 1,
            ]
        );

        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_CZECH_POST);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CASH_ON_DELIVERY);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_NEW);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $orderData->street = 'Adresní 6';
        $orderData->city = 'Opava';
        $orderData->postcode = '72589';
        $orderData->deliveryFirstName = 'Pavel';
        $orderData->deliveryLastName = 'Novák';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->deliveryTelephone = '+420605123654';
        $orderData->deliveryStreet = 'Adresní 6';
        $orderData->deliveryCity = 'Opava';
        $orderData->deliveryPostcode = '72589';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $orderData->deliveryAddressSameAsBillingAddress = true;
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $orderData->createdAsAdministrator = $this->getReference(AdministratorDataFixture::ADMINISTRATOR);
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '10' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '20' => 4,
            ]
        );

        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_PPL);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CARD);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_DONE);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->deliveryFirstName = 'Pavla';
        $orderData->deliveryLastName = 'Adámková';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->deliveryTelephone = '+4206051836459';
        $orderData->deliveryStreet = 'Výpočetni 16';
        $orderData->deliveryCity = 'Praha';
        $orderData->deliveryPostcode = '30015';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->deliveryAddressSameAsBillingAddress = false;
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '15' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '18' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '19' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '3' => 1,
            ]
        );

        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_PERSONAL);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CASH);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_IN_PROGRESS);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $orderData->deliveryFirstName = 'Adam';
        $orderData->deliveryLastName = 'Žitný';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->deliveryTelephone = '+4206051836459';
        $orderData->deliveryStreet = 'Přímá 1';
        $orderData->deliveryCity = 'Plzeň';
        $orderData->deliveryPostcode = '30010';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $orderData->deliveryAddressSameAsBillingAddress = false;
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '9' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '19' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '6' => 1,
            ]
        );

        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_PPL);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CARD);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_NEW);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->street = 'fakt. Křivá 11';
        $orderData->city = 'fakt. Jablonec';
        $orderData->postcode = '78952';
        $orderData->deliveryFirstName = 'Radim';
        $orderData->deliveryLastName = 'Svátek';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->deliveryTelephone = '+420733598748';
        $orderData->deliveryStreet = 'Křivá 11';
        $orderData->deliveryCity = 'Jablonec';
        $orderData->deliveryPostcode = '78952';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->deliveryAddressSameAsBillingAddress = false;
        $orderData->companyName = 'BestCompanyEver, s.r.o.';
        $orderData->companyNumber = '555555';
        $orderData->note = 'Doufám, že vše dorazí v pořádku a co nejdříve :)';
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '7' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '17' => 6,
                ProductDataFixture::PRODUCT_PREFIX . '9' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '14' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '10' => 2,
            ]
        );

        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_PPL);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CARD);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_NEW);
        $orderData->firstName = 'Radim';
        $orderData->lastName = 'Svátek';
        $orderData->email = 'vitek@shopsys.com';
        $orderData->telephone = '+420733598748';
        $orderData->street = 'Křivá 11';
        $orderData->city = 'Jablonec';
        $orderData->postcode = '78952';
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->deliveryAddressSameAsBillingAddress = false;
        $orderData->deliveryCity = 'Ostrava';
        $orderData->deliveryCompanyName = 'BestCompanyEver, s.r.o.';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $orderData->deliveryStreet = 'Křivá 11';
        $orderData->deliveryTelephone = '+421555444';
        $orderData->deliveryPostcode = '01305';
        $orderData->deliveryFirstName = 'Pavol';
        $orderData->deliveryLastName = 'Svátek';
        $orderData->companyName = 'BestCompanyEver, s.r.o.';
        $orderData->companyNumber = '555555';
        $orderData->note = 'Doufám, že vše dorazí v pořádku a co nejdříve :)';
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $orderData->createdAsAdministrator = $this->getReference(AdministratorDataFixture::ADMINISTRATOR);
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '7' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '17' => 6,
                ProductDataFixture::PRODUCT_PREFIX . '9' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '14' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '10' => 2,
            ]
        );

        $customerUser = $this->customerUserRepository->findCustomerUserByEmailAndDomain('vitek@shopsys.com', $domainId);
        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_PPL);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CARD);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_NEW);
        $orderData->firstName = 'Radim';
        $orderData->lastName = 'Svátek';
        $orderData->email = 'vitek@shopsys.com';
        $orderData->telephone = '+420733598748';
        $orderData->street = 'Křivá 11';
        $orderData->city = 'Jablonec';
        $orderData->postcode = '78952';
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->deliveryAddressSameAsBillingAddress = false;
        $orderData->deliveryCity = 'Ostrava';
        $orderData->deliveryCompanyName = 'BestCompanyEver, s.r.o.';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $orderData->deliveryStreet = 'Křivá 11';
        $orderData->deliveryTelephone = '+421555444';
        $orderData->deliveryPostcode = '01305';
        $orderData->deliveryFirstName = 'Pavol';
        $orderData->deliveryLastName = 'Svátek';
        $orderData->companyName = 'BestCompanyEver, s.r.o.';
        $orderData->companyNumber = '555555';
        $orderData->note = 'Doufám, že vše dorazí v pořádku a co nejdříve :)';
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '7' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '17' => 6,
                ProductDataFixture::PRODUCT_PREFIX . '9' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '14' => 1,
                ProductDataFixture::PRODUCT_PREFIX . '10' => 2,
            ],
            $customerUser
        );
    }

    /**
     * @param int $domainId
     */
    protected function loadDistinct(int $domainId)
    {
        $domainDefaultCurrency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainId);

        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_CZECH_POST);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CASH_ON_DELIVERY);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_IN_PROGRESS);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->deliveryFirstName = 'Václav';
        $orderData->deliveryLastName = 'Svěrkoš';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->deliveryTelephone = '+420725711368';
        $orderData->deliveryStreet = 'Devátá 25';
        $orderData->deliveryCity = 'Ostrava';
        $orderData->deliveryPostcode = '71200';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->deliveryAddressSameAsBillingAddress = false;
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '14' => 1,
            ]
        );

        $customerUser = $this->customerUserRepository->findCustomerUserByEmailAndDomain('no-reply.2@shopsys.com', $domainId);
        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_PERSONAL);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CASH);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_NEW);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->firstName = 'Jan';
        $orderData->lastName = 'Novák';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->telephone = '+420123456789';
        $orderData->street = 'Pouliční 11';
        $orderData->city = 'Městník';
        $orderData->postcode = '12345';
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->companyName = 'shopsys s.r.o.';
        $orderData->companyNumber = '123456789';
        $orderData->companyTaxNumber = '987654321';
        $orderData->deliveryAddressSameAsBillingAddress = false;
        $orderData->deliveryFirstName = 'Karel';
        $orderData->deliveryLastName = 'Vesela';
        $orderData->deliveryCompanyName = 'Bestcompany';
        $orderData->deliveryTelephone = '+420987654321';
        $orderData->deliveryStreet = 'Zakopaná 42';
        $orderData->deliveryCity = 'Zemín';
        $orderData->deliveryPostcode = '54321';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA);
        $orderData->note = 'Prosím o dodání do pátku. Děkuji.';
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '1' => 2,
                ProductDataFixture::PRODUCT_PREFIX . '3' => 1,
            ],
            $customerUser
        );

        $customerUser = $this->customerUserRepository->findCustomerUserByEmailAndDomain('no-reply.7@shopsys.com', $domainId);
        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_CZECH_POST);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CASH_ON_DELIVERY);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_NEW);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->deliveryFirstName = 'Jindřich';
        $orderData->deliveryLastName = 'Němec';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->deliveryTelephone = '+420123456789';
        $orderData->deliveryStreet = 'Sídlištní 3259';
        $orderData->deliveryCity = 'Orlová';
        $orderData->deliveryPostcode = '65421';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->deliveryAddressSameAsBillingAddress = false;
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '2' => 2,
                ProductDataFixture::PRODUCT_PREFIX . '4' => 4,
            ],
            $customerUser
        );

        $orderData = $this->orderDataFactory->create();
        $orderData->transport = $this->getReference(TransportDataFixture::TRANSPORT_PERSONAL);
        $orderData->payment = $this->getReference(PaymentDataFixture::PAYMENT_CASH);
        $orderData->status = $this->getReference(OrderStatusDataFixture::ORDER_STATUS_CANCELED);
        $orderData->country = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->deliveryFirstName = 'Viktor';
        $orderData->deliveryLastName = 'Pátek';
        $orderData->email = 'no-reply@shopsys.com';
        $orderData->deliveryTelephone = '+420888777111';
        $orderData->deliveryStreet = 'Vyhlídková 88';
        $orderData->deliveryCity = 'Ostrava';
        $orderData->deliveryPostcode = '71201';
        $orderData->deliveryCountry = $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);
        $orderData->deliveryAddressSameAsBillingAddress = false;
        $orderData->domainId = $domainId;
        $orderData->currency = $domainDefaultCurrency;
        $orderData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
        $this->createOrder(
            $orderData,
            [
                ProductDataFixture::PRODUCT_PREFIX . '3' => 10,
            ]
        );
    }

    /**
     * @param \App\Model\Order\OrderData $orderData
     * @param array $products
     * @param \App\Model\Customer\User\CustomerUser $customerUser
     */
    protected function createOrder(
        OrderData $orderData,
        array $products,
        ?CustomerUser $customerUser = null
    ) {
        $quantifiedProducts = [];
        foreach ($products as $productReferenceName => $quantity) {
            $product = $this->getReference($productReferenceName);
            $quantifiedProducts[] = new QuantifiedProduct($product, $quantity);
        }
        $orderData->exportStatus = Order::EXPORT_SUCCESS;
        $orderData->exportedAt = $orderData->createdAt;

        $orderPreview = $this->orderPreviewFactory->create(
            $orderData->currency,
            $orderData->domainId,
            $quantifiedProducts,
            $orderData->transport,
            $orderData->payment,
            $customerUser,
            null
        );

        $order = $this->orderFacade->createOrder($orderData, $orderPreview, $customerUser);
        /* @var $order \App\Model\Order\Order */

        $referenceName = self::ORDER_PREFIX . $order->getId();
        $this->addReference($referenceName, $order);
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            ProductDataFixture::class,
            TransportDataFixture::class,
            PaymentDataFixture::class,
            CustomerUserDataFixture::class,
            OrderStatusDataFixture::class,
            CountryDataFixture::class,
            SettingValueDataFixture::class,
        ];
    }
}
