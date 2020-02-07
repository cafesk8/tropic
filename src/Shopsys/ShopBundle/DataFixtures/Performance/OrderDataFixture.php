<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\DataFixtures\Performance;

use Doctrine\ORM\EntityManagerInterface;
use Faker\Generator as Faker;
use Shopsys\FrameworkBundle\Component\Console\ProgressBarFactory;
use Shopsys\FrameworkBundle\Component\DataFixture\PersistentReferenceFacade;
use Shopsys\FrameworkBundle\Component\Doctrine\SqlLoggerFacade;
use Shopsys\FrameworkBundle\Model\Customer\CustomerFacade;
use Shopsys\FrameworkBundle\Model\Customer\User;
use Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedProduct;
use Shopsys\FrameworkBundle\Model\Order\OrderDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\OrderFacade;
use Shopsys\FrameworkBundle\Model\Order\Preview\OrderPreviewFactory;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductFacade;
use Shopsys\ShopBundle\DataFixtures\Demo\CountryDataFixture;
use Shopsys\ShopBundle\DataFixtures\Demo\CurrencyDataFixture;
use Shopsys\ShopBundle\DataFixtures\Demo\OrderStatusDataFixture;
use Shopsys\ShopBundle\DataFixtures\Demo\PaymentDataFixture;
use Shopsys\ShopBundle\DataFixtures\Demo\TransportDataFixture;
use Shopsys\ShopBundle\DataFixtures\Performance\ProductDataFixture as PerformanceProductDataFixture;
use Shopsys\ShopBundle\DataFixtures\Performance\UserDataFixture as PerformanceUserDataFixture;
use Symfony\Component\Console\Output\OutputInterface;

class OrderDataFixture
{
    public const PERCENTAGE_OF_ORDERS_BY_REGISTERED_USERS = 25;

    public const BATCH_SIZE = 10;

    /**
     * @var int
     */
    private $orderTotalCount;

    /**
     * @var int
     */
    private $orderItemCountPerOrder;

    /**
     * @var int[]
     */
    private $performanceProductIds;

    /**
     * @var int[]
     */
    private $performanceUserIds;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Doctrine\SqlLoggerFacade
     */
    private $sqlLoggerFacade;

    /**
     * @var \Faker\Generator
     */
    private $faker;

    /**
     * @var \Shopsys\FrameworkBundle\Component\DataFixture\PersistentReferenceFacade
     */
    private $persistentReferenceFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\Preview\OrderPreviewFactory
     */
    private $orderPreviewFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Customer\CustomerFacade
     */
    private $customerFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Console\ProgressBarFactory
     */
    private $progressBarFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\OrderDataFactory
     */
    private $orderDataFactory;

    /**
     * @param int $orderTotalCount
     * @param int $orderItemCountPerOrder
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Component\Doctrine\SqlLoggerFacade $sqlLoggerFacade
     * @param \Faker\Generator $faker
     * @param \Shopsys\FrameworkBundle\Component\DataFixture\PersistentReferenceFacade $persistentReferenceFacade
     * @param \Shopsys\ShopBundle\Model\Order\OrderFacade $orderFacade
     * @param \Shopsys\ShopBundle\Model\Order\Preview\OrderPreviewFactory $orderPreviewFactory
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\FrameworkBundle\Component\Console\ProgressBarFactory $progressBarFactory
     * @param \Shopsys\ShopBundle\Model\Order\OrderDataFactory $orderDataFactory
     */
    public function __construct(
        $orderTotalCount,
        $orderItemCountPerOrder,
        EntityManagerInterface $em,
        SqlLoggerFacade $sqlLoggerFacade,
        Faker $faker,
        PersistentReferenceFacade $persistentReferenceFacade,
        OrderFacade $orderFacade,
        OrderPreviewFactory $orderPreviewFactory,
        ProductFacade $productFacade,
        CustomerFacade $customerFacade,
        ProgressBarFactory $progressBarFactory,
        OrderDataFactoryInterface $orderDataFactory
    ) {
        $this->orderTotalCount = $orderTotalCount;
        $this->orderItemCountPerOrder = $orderItemCountPerOrder;
        $this->performanceProductIds = [];
        $this->em = $em;
        $this->sqlLoggerFacade = $sqlLoggerFacade;
        $this->faker = $faker;
        $this->persistentReferenceFacade = $persistentReferenceFacade;
        $this->orderFacade = $orderFacade;
        $this->orderPreviewFactory = $orderPreviewFactory;
        $this->productFacade = $productFacade;
        $this->customerFacade = $customerFacade;
        $this->progressBarFactory = $progressBarFactory;
        $this->orderDataFactory = $orderDataFactory;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function load(OutputInterface $output)
    {
        // Sql logging during mass data import makes memory leak
        $this->sqlLoggerFacade->temporarilyDisableLogging();

        $this->loadPerformanceProductIds();
        $this->loadPerformanceUserIdsOnFirstDomain();

        $progressBar = $this->progressBarFactory->create($output, $this->orderTotalCount);

        for ($orderIndex = 0; $orderIndex < $this->orderTotalCount; $orderIndex++) {
            $this->createOrder();

            $progressBar->advance();

            if ($orderIndex % self::BATCH_SIZE === 0) {
                $this->em->clear();
            }
        }

        $progressBar->finish();

        $this->sqlLoggerFacade->reenableLogging();
    }

    private function createOrder()
    {
        $user = $this->getRandomUserOrNull();
        $orderData = $this->createOrderData($user);
        $quantifiedProducts = $this->createQuantifiedProducts();

        $orderPreview = $this->orderPreviewFactory->create(
            $orderData->currency,
            $orderData->domainId,
            $quantifiedProducts,
            $orderData->transport,
            $orderData->payment,
            $user,
            null
        );

        $this->orderFacade->createOrder($orderData, $orderPreview, $user);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\User $user
     * @return \Shopsys\ShopBundle\Model\Order\OrderData
     */
    private function createOrderData(?User $user = null)
    {
        $orderData = $this->orderDataFactory->create();

        if ($user !== null) {
            $orderData->firstName = $user->getFirstName();
            $orderData->lastName = $user->getLastName();
            $orderData->email = $user->getEmail();

            $billingAddress = $user->getBillingAddress();
            $orderData->telephone = $user->getTelephone();
            $orderData->street = $billingAddress->getStreet();
            $orderData->city = $billingAddress->getCity();
            $orderData->postcode = $billingAddress->getPostcode();
            $orderData->country = $billingAddress->getCountry();
            $orderData->companyName = $billingAddress->getCompanyName();
            $orderData->companyNumber = $billingAddress->getCompanyNumber();
            $orderData->companyTaxNumber = $billingAddress->getCompanyTaxNumber();
        } else {
            $orderData->firstName = $this->faker->firstName;
            $orderData->lastName = $this->faker->lastName;
            $orderData->email = $this->faker->safeEmail;
            $orderData->telephone = $this->faker->phoneNumber;
            $orderData->street = $this->faker->streetAddress;
            $orderData->city = $this->faker->city;
            $orderData->postcode = $this->faker->postcode;
            $orderData->country = $this->getRandomCountryFromFirstDomain();
            $orderData->companyName = $this->faker->company;
            $orderData->companyNumber = (string)$this->faker->randomNumber(6);
            $orderData->companyTaxNumber = (string)$this->faker->randomNumber(6);
        }

        $orderData->transport = $this->getRandomTransport();
        $orderData->payment = $this->getRandomPayment();
        $orderData->status = $this->persistentReferenceFacade->getReference(OrderStatusDataFixture::ORDER_STATUS_DONE);
        $orderData->deliveryAddressSameAsBillingAddress = false;
        $orderData->deliveryFirstName = $this->faker->firstName;
        $orderData->deliveryLastName = $this->faker->lastName;
        $orderData->deliveryCompanyName = $this->faker->company;
        $orderData->deliveryTelephone = $this->faker->phoneNumber;
        $orderData->deliveryStreet = $this->faker->streetAddress;
        $orderData->deliveryCity = $this->faker->city;
        $orderData->deliveryPostcode = $this->faker->postcode;
        $orderData->deliveryCountry = $this->getRandomCountryFromFirstDomain();
        $orderData->note = $this->faker->text(200);
        $orderData->createdAt = $this->faker->dateTimeBetween('-1 year', 'now');
        $orderData->domainId = 1;
        $orderData->currency = $this->persistentReferenceFacade->getReference(CurrencyDataFixture::CURRENCY_CZK);

        return $orderData;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Order\Item\QuantifiedProduct[]
     */
    private function createQuantifiedProducts()
    {
        $quantifiedProducts = [];

        $randomProductIds = $this->getRandomPerformanceProductIds($this->orderItemCountPerOrder);
        foreach ($randomProductIds as $randomProductId) {
            $product = $this->productFacade->getById($randomProductId);
            $quantity = $this->faker->numberBetween(1, 10);

            $quantifiedProducts[] = new QuantifiedProduct($product, $quantity);
        }

        return $quantifiedProducts;
    }

    private function loadPerformanceProductIds()
    {
        $firstPerformanceProduct = $this->persistentReferenceFacade->getReference(
            PerformanceProductDataFixture::FIRST_PERFORMANCE_PRODUCT
        );
        /* @var $firstPerformanceProduct \Shopsys\ShopBundle\Model\Product\Product */

        $qb = $this->em->createQueryBuilder()
            ->select('p.id')
            ->from(Product::class, 'p')
            ->where('p.id >= :firstPerformanceProductId')
            ->andWhere('p.variantType != :mainVariantType')
            ->setParameter('firstPerformanceProductId', $firstPerformanceProduct->getId())
            ->setParameter('mainVariantType', Product::VARIANT_TYPE_MAIN);

        $this->performanceProductIds = array_column($qb->getQuery()->getScalarResult(), 'id');
    }

    /**
     * @param int $count
     * @return int[]
     */
    private function getRandomPerformanceProductIds($count)
    {
        return $this->faker->randomElements($this->performanceProductIds, $count);
    }

    private function loadPerformanceUserIdsOnFirstDomain()
    {
        $firstPerformanceUser = $this->persistentReferenceFacade->getReference(
            PerformanceUserDataFixture::FIRST_PERFORMANCE_USER
        );
        /* @var $firstPerformanceUser \Shopsys\ShopBundle\Model\Customer\User */

        $qb = $this->em->createQueryBuilder()
            ->select('u.id')
            ->from(User::class, 'u')
            ->where('u.id >= :firstPerformanceUserId')
            ->andWhere('u.domainId = :domainId')
            ->setParameter('firstPerformanceUserId', $firstPerformanceUser->getId())
            ->setParameter('domainId', 1);

        $this->performanceUserIds = array_column($qb->getQuery()->getScalarResult(), 'id');
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Customer\User|null
     */
    private function getRandomUserOrNull()
    {
        $shouldBeRegisteredUser = $this->faker->boolean(self::PERCENTAGE_OF_ORDERS_BY_REGISTERED_USERS);

        if ($shouldBeRegisteredUser) {
            $userId = $this->faker->randomElement($this->performanceUserIds);
            return $this->customerFacade->getUserById($userId);
        } else {
            return null;
        }
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Transport\Transport
     */
    private function getRandomTransport()
    {
        $randomTransportReferenceName = $this->faker->randomElement([
            TransportDataFixture::TRANSPORT_CZECH_POST,
            TransportDataFixture::TRANSPORT_PPL,
            TransportDataFixture::TRANSPORT_PERSONAL,
        ]);

        return $this->persistentReferenceFacade->getReference($randomTransportReferenceName);
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Payment\Payment
     */
    private function getRandomPayment()
    {
        $randomPaymentReferenceName = $this->faker->randomElement([
            PaymentDataFixture::PAYMENT_CARD,
            PaymentDataFixture::PAYMENT_CASH_ON_DELIVERY,
            PaymentDataFixture::PAYMENT_CASH,
        ]);

        return $this->persistentReferenceFacade->getReference($randomPaymentReferenceName);
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Country\Country
     */
    private function getRandomCountryFromFirstDomain()
    {
        $randomPaymentReferenceName = $this->faker->randomElement([
            CountryDataFixture::COUNTRY_CZECH_REPUBLIC,
            CountryDataFixture::COUNTRY_SLOVAKIA,
        ]);

        return $this->persistentReferenceFacade->getReference($randomPaymentReferenceName);
    }
}
