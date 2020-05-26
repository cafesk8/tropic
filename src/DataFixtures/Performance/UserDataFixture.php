<?php

declare(strict_types=1);

namespace App\DataFixtures\Performance;

use App\DataFixtures\Demo\CountryDataFixture;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Generator as Faker;
use Shopsys\FrameworkBundle\Component\Console\ProgressBarFactory;
use Shopsys\FrameworkBundle\Component\DataFixture\PersistentReferenceFacade;
use Shopsys\FrameworkBundle\Component\Doctrine\SqlLoggerFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Customer\BillingAddressDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFacade;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateDataFactoryInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserDataFixture
{
    public const FIRST_PERFORMANCE_USER = 'first_performance_user';

    /**
     * @var int
     */
    private $userCountPerDomain;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Component\Doctrine\SqlLoggerFacade
     */
    private $sqlLoggerFacade;

    /**
     * @var \App\Model\Customer\User\CustomerUserFacade
     */
    private $customerEditFacade;

    /**
     * @var \App\Model\Customer\User\CustomerUserDataFactory
     */
    private $customerUserDataFactory;

    /**
     * @var \Faker\Generator
     */
    private $faker;

    /**
     * @var \Shopsys\FrameworkBundle\Component\DataFixture\PersistentReferenceFacade
     */
    private $persistentReferenceFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Console\ProgressBarFactory
     */
    private $progressBarFactory;

    /**
     * @var \App\Model\Customer\User\CustomerUserUpdateDataFactory
     */
    private $customerUserUpdateDataFactory;

    /**
     * @var \App\Model\Customer\BillingAddressDataFactory
     */
    private $billingAddressDataFactory;

    /**
     * @var \App\Model\Customer\DeliveryAddressDataFactory
     */
    private $deliveryAddressDataFactory;

    /**
     * @param int $userCountPerDomain
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Component\Doctrine\SqlLoggerFacade $sqlLoggerFacade
     * @param \App\Model\Customer\User\CustomerUserFacade $customerEditFacade
     * @param \App\Model\Customer\User\CustomerUserDataFactory $customerUserDataFactory
     * @param \Faker\Generator $faker
     * @param \Shopsys\FrameworkBundle\Component\DataFixture\PersistentReferenceFacade $persistentReferenceFacade
     * @param \Shopsys\FrameworkBundle\Component\Console\ProgressBarFactory $progressBarFactory
     * @param \App\Model\Customer\User\CustomerUserUpdateDataFactory $customerUserUpdateDataFactory
     * @param \App\Model\Customer\BillingAddressDataFactory $billingAddressDataFactory
     * @param \App\Model\Customer\DeliveryAddressDataFactory $deliveryAddressDataFactory
     */
    public function __construct(
        $userCountPerDomain,
        EntityManagerInterface $em,
        Domain $domain,
        SqlLoggerFacade $sqlLoggerFacade,
        CustomerUserFacade $customerEditFacade,
        CustomerUserDataFactoryInterface $customerUserDataFactory,
        Faker $faker,
        PersistentReferenceFacade $persistentReferenceFacade,
        ProgressBarFactory $progressBarFactory,
        CustomerUserUpdateDataFactoryInterface $customerUserUpdateDataFactory,
        BillingAddressDataFactoryInterface $billingAddressDataFactory,
        DeliveryAddressDataFactoryInterface $deliveryAddressDataFactory
    ) {
        $this->em = $em;
        $this->domain = $domain;
        $this->sqlLoggerFacade = $sqlLoggerFacade;
        $this->customerEditFacade = $customerEditFacade;
        $this->customerUserDataFactory = $customerUserDataFactory;
        $this->faker = $faker;
        $this->persistentReferenceFacade = $persistentReferenceFacade;
        $this->userCountPerDomain = $userCountPerDomain;
        $this->progressBarFactory = $progressBarFactory;
        $this->customerUserUpdateDataFactory = $customerUserUpdateDataFactory;
        $this->billingAddressDataFactory = $billingAddressDataFactory;
        $this->deliveryAddressDataFactory = $deliveryAddressDataFactory;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function load(OutputInterface $output)
    {
        // Sql logging during mass data import makes memory leak
        $this->sqlLoggerFacade->temporarilyDisableLogging();
        $domains = $this->domain->getAll();

        $progressBar = $this->progressBarFactory->create($output, count($domains) * $this->userCountPerDomain);

        $isFirstUser = true;

        foreach ($domains as $domainConfig) {
            for ($i = 0; $i < $this->userCountPerDomain; $i++) {
                $customerUser = $this->createCustomerOnDomain($domainConfig->getId(), $i);
                $progressBar->advance();

                if ($isFirstUser) {
                    $this->persistentReferenceFacade->persistReference(self::FIRST_PERFORMANCE_USER, $customerUser);
                    $isFirstUser = false;
                }

                $this->em->clear();
            }
        }

        $this->sqlLoggerFacade->reenableLogging();
    }

    /**
     * @param int $domainId
     * @param int $userNumber
     * @return \App\Model\Customer\User\CustomerUser
     */
    private function createCustomerOnDomain($domainId, $userNumber)
    {
        $customerUserUpdateData = $this->getRandomCustomerDataByDomainId($domainId, $userNumber);

        return $this->customerEditFacade->create($customerUserUpdateData);
    }

    /**
     * @param int $domainId
     * @param int $userNumber
     * @return \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData
     */
    private function getRandomCustomerDataByDomainId($domainId, $userNumber)
    {
        $customerUserUpdateData = $this->customerUserUpdateDataFactory->create();

        $country = $this->persistentReferenceFacade->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC);

        $customerUserData = $this->customerUserDataFactory->createForDomainId($domainId);
        $customerUserData->firstName = $this->faker->firstName;
        $customerUserData->lastName = $this->faker->lastName;
        $customerUserData->email = $userNumber . '.' . $this->faker->safeEmail;
        $customerUserData->password = $this->faker->password;
        $customerUserData->domainId = $domainId;
        $customerUserData->createdAt = $this->faker->dateTimeBetween('-1 year', 'now');
        $customerUserData->telephone = $this->faker->phoneNumber;

        $customerUserUpdateData->customerUserData = $customerUserData;

        $billingAddressData = $this->billingAddressDataFactory->create();
        $billingAddressData->companyCustomer = $this->faker->boolean();
        if ($billingAddressData->companyCustomer === true) {
            $billingAddressData->companyName = $this->faker->company;
            $billingAddressData->companyNumber = (string)$this->faker->randomNumber(6);
            $billingAddressData->companyTaxNumber = (string)$this->faker->randomNumber(6);
        }
        $billingAddressData->street = $this->faker->streetAddress;
        $billingAddressData->city = $this->faker->city;
        $billingAddressData->postcode = $this->faker->postcode;
        $billingAddressData->country = $country;
        $customerUserUpdateData->billingAddressData = $billingAddressData;

        $deliveryAddressData = $this->deliveryAddressDataFactory->create();
        $deliveryAddressData->addressFilled = true;
        $deliveryAddressData->city = $this->faker->city;
        $deliveryAddressData->companyName = $this->faker->company;
        $deliveryAddressData->firstName = $this->faker->firstName;
        $deliveryAddressData->lastName = $this->faker->lastName;
        $deliveryAddressData->postcode = $this->faker->postcode;
        $deliveryAddressData->country = $country;
        $deliveryAddressData->street = $this->faker->streetAddress;
        $deliveryAddressData->telephone = $this->faker->phoneNumber;
        $customerUserUpdateData->deliveryAddressData = $deliveryAddressData;

        return $customerUserUpdateData;
    }
}
