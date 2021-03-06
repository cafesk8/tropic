<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Model\Pricing\Group\PricingGroupFacade;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Generator;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator;
use Shopsys\FrameworkBundle\Component\String\HashGenerator;
use Shopsys\FrameworkBundle\Model\Customer\BillingAddressDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\CustomerFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\DeliveryAddressDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUser;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFacade;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserPasswordFacade;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateDataFactoryInterface;

class CustomerUserDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    public const USER_WITH_RESET_PASSWORD_HASH = 'user_with_reset_password_hash';

    protected const KEY_CUSTOMER_USER_DATA = 'customerUserData';
    protected const KEY_BILLING_ADDRESS = 'billingAddress';
    protected const KEY_DELIVERY_ADDRESS = 'deliveryAddress';

    protected const KEY_CUSTOMER_USER_DATA_FIRST_NAME = 'firstName';
    protected const KEY_CUSTOMER_USER_DATA_LAST_NAME = 'lastName';
    protected const KEY_CUSTOMER_USER_DATA_EMAIL = 'email';
    protected const KEY_CUSTOMER_USER_DATA_PASSWORD = 'password';
    protected const KEY_CUSTOMER_USER_DATA_TELEPHONE = 'telephone';

    protected const KEY_ADDRESS_COMPANY_CUSTOMER = 'companyCustomer';
    protected const KEY_ADDRESS_COMPANY_NAME = 'companyName';
    protected const KEY_ADDRESS_COMPANY_NUMBER = 'companyNumber';
    protected const KEY_ADDRESS_STREET = 'street';
    protected const KEY_ADDRESS_CITY = 'city';
    protected const KEY_ADDRESS_POSTCODE = 'postcode';
    protected const KEY_ADDRESS_COUNTRY = 'country';
    protected const KEY_ADDRESS_ADDRESS_FILLED = 'addressFilled';
    protected const KEY_ADDRESS_TELEPHONE = 'telephone';
    protected const KEY_ADDRESS_FIRST_NAME = 'firstName';
    protected const KEY_ADDRESS_LAST_NAME = 'lastName';

    /**
     * @var \App\Model\Customer\User\CustomerUserFacade
     */
    protected $customerUserFacade;

    /**
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    protected $em;

    /**
     * @var \App\Component\String\HashGenerator
     */
    protected $hashGenerator;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @var \App\Model\Customer\User\CustomerUserUpdateDataFactory
     */
    protected $customerUserUpdateDataFactory;

    /**
     * @var \App\Model\Customer\User\CustomerUserDataFactory
     */
    protected $customerUserDataFactory;

    /**
     * @var \App\Model\Customer\BillingAddressDataFactory
     */
    protected $billingAddressDataFactory;

    /**
     * @var \App\Model\Customer\DeliveryAddressDataFactory
     */
    protected $deliveryAddressDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\CustomerFactoryInterface
     */
    protected $customerFactory;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @param \App\Model\Customer\User\CustomerUserFacade $customerUserFacade
     * @param \Faker\Generator $faker
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
     * @param \App\Component\String\HashGenerator $hashGenerator
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Customer\User\CustomerUserUpdateDataFactory $customerUserUpdateDataFactory
     * @param \App\Model\Customer\User\CustomerUserDataFactory $customerUserDataFactory
     * @param \App\Model\Customer\BillingAddressDataFactory $billingAddressDataFactory
     * @param \App\Model\Customer\DeliveryAddressDataFactory $deliveryAddressDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Customer\CustomerFactoryInterface $customerFactory
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     */
    public function __construct(
        CustomerUserFacade $customerUserFacade,
        Generator $faker,
        EntityManagerDecorator $em,
        HashGenerator $hashGenerator,
        Domain $domain,
        CustomerUserUpdateDataFactoryInterface $customerUserUpdateDataFactory,
        CustomerUserDataFactoryInterface $customerUserDataFactory,
        BillingAddressDataFactoryInterface $billingAddressDataFactory,
        DeliveryAddressDataFactoryInterface $deliveryAddressDataFactory,
        CustomerFactoryInterface $customerFactory,
        PricingGroupFacade $pricingGroupFacade
    ) {
        $this->customerUserFacade = $customerUserFacade;
        $this->faker = $faker;
        $this->em = $em;
        $this->hashGenerator = $hashGenerator;
        $this->domain = $domain;
        $this->customerUserUpdateDataFactory = $customerUserUpdateDataFactory;
        $this->customerUserDataFactory = $customerUserDataFactory;
        $this->billingAddressDataFactory = $billingAddressDataFactory;
        $this->deliveryAddressDataFactory = $deliveryAddressDataFactory;
        $this->customerFactory = $customerFactory;
        $this->pricingGroupFacade = $pricingGroupFacade;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->domain->getAll() as $domainConfig) {
            $domainId = $domainConfig->getId();
            if ($domainId === Domain::SECOND_DOMAIN_ID) {
                $customersDataProvider = $this->getDistinctCustomerUsersDataProvider();
            } else {
                $customersDataProvider = $this->getDefaultCustomerUsersDataProvider();
            }

            foreach ($customersDataProvider as $customerDataProvider) {
                $customerUserUpdateData = $this->getCustomerUserUpdateData($domainId, $customerDataProvider);
                $customerUserUpdateData->customerUserData->createdAt = $this->faker->dateTimeBetween('-1 week', 'now');

                $customer = $this->customerUserFacade->create($customerUserUpdateData);
                if ($customer->getId() === 1) {
                    $this->resetPassword($customer);
                    $this->addReference(self::USER_WITH_RESET_PASSWORD_HASH, $customer);
                }
            }
        }
    }

    /**
     * @param int $domainId
     * @param array $data
     *
     * @return \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateData
     */
    protected function getCustomerUserUpdateData(int $domainId, array $data): CustomerUserUpdateData
    {
        $customerUserUpdateData = $this->customerUserUpdateDataFactory->create();
        $customerUserData = $this->customerUserDataFactory->createForDomainId($domainId);

        foreach ($this->pricingGroupFacade->getByDomainId($domainId) as $pricingGroup) {
            if ($pricingGroup->isRegisteredCustomerPricingGroup()) {
                $customerUserData->pricingGroup = $pricingGroup;
                break;
            }
        }

        $customerUserData->firstName = $data[self::KEY_CUSTOMER_USER_DATA][self::KEY_CUSTOMER_USER_DATA_FIRST_NAME] ?? null;
        $customerUserData->lastName = $data[self::KEY_CUSTOMER_USER_DATA][self::KEY_CUSTOMER_USER_DATA_LAST_NAME] ?? null;
        $customerUserData->email = $data[self::KEY_CUSTOMER_USER_DATA][self::KEY_CUSTOMER_USER_DATA_EMAIL] ?? null;
        $customerUserData->password = $data[self::KEY_CUSTOMER_USER_DATA][self::KEY_CUSTOMER_USER_DATA_PASSWORD] ?? null;
        $customerUserData->telephone = $data[self::KEY_CUSTOMER_USER_DATA][self::KEY_CUSTOMER_USER_DATA_TELEPHONE] ?? null;
        $customerUserData->customer = $customerUserUpdateData->customerUserData->customer;

        $billingAddressData = $customerUserUpdateData->billingAddressData;
        $billingAddressData->companyCustomer = $data[self::KEY_BILLING_ADDRESS][self::KEY_ADDRESS_COMPANY_CUSTOMER];
        $billingAddressData->companyName = $data[self::KEY_BILLING_ADDRESS][self::KEY_ADDRESS_COMPANY_NAME] ?? null;
        $billingAddressData->companyNumber = $data[self::KEY_BILLING_ADDRESS][self::KEY_ADDRESS_COMPANY_NUMBER] ?? null;
        $billingAddressData->city = $data[self::KEY_BILLING_ADDRESS][self::KEY_ADDRESS_CITY] ?? null;
        $billingAddressData->street = $data[self::KEY_BILLING_ADDRESS][self::KEY_ADDRESS_STREET] ?? null;
        $billingAddressData->postcode = $data[self::KEY_BILLING_ADDRESS][self::KEY_ADDRESS_POSTCODE] ?? null;
        $billingAddressData->country = $data[self::KEY_BILLING_ADDRESS][self::KEY_ADDRESS_COUNTRY];

        if (isset($data[self::KEY_DELIVERY_ADDRESS])) {
            $deliveryAddressData = $customerUserUpdateData->deliveryAddressData;
            $deliveryAddressData->addressFilled = $data[self::KEY_DELIVERY_ADDRESS][self::KEY_ADDRESS_ADDRESS_FILLED] ?? null;
            $deliveryAddressData->companyName = $data[self::KEY_DELIVERY_ADDRESS][self::KEY_ADDRESS_COMPANY_NAME] ?? null;
            $deliveryAddressData->firstName = $data[self::KEY_DELIVERY_ADDRESS][self::KEY_ADDRESS_FIRST_NAME] ?? null;
            $deliveryAddressData->lastName = $data[self::KEY_DELIVERY_ADDRESS][self::KEY_ADDRESS_LAST_NAME] ?? null;
            $deliveryAddressData->city = $data[self::KEY_DELIVERY_ADDRESS][self::KEY_ADDRESS_CITY] ?? null;
            $deliveryAddressData->postcode = $data[self::KEY_DELIVERY_ADDRESS][self::KEY_ADDRESS_POSTCODE] ?? null;
            $deliveryAddressData->street = $data[self::KEY_DELIVERY_ADDRESS][self::KEY_ADDRESS_STREET] ?? null;
            $deliveryAddressData->telephone = $data[self::KEY_DELIVERY_ADDRESS][self::KEY_ADDRESS_TELEPHONE] ?? null;
            $deliveryAddressData->country = $data[self::KEY_DELIVERY_ADDRESS][self::KEY_ADDRESS_COUNTRY];
        }

        $customerUserUpdateData->customerUserData = $customerUserData;
        $customerUserUpdateData->billingAddressData = $billingAddressData;

        return $customerUserUpdateData;
    }

    /**
     * @return array
     */
    protected function getDefaultCustomerUsersDataProvider(): array
    {
        return [
            [
                self::KEY_CUSTOMER_USER_DATA => [
                    self::KEY_CUSTOMER_USER_DATA_FIRST_NAME => 'Jarom??r',
                    self::KEY_CUSTOMER_USER_DATA_LAST_NAME => 'J??gr',
                    self::KEY_CUSTOMER_USER_DATA_EMAIL => 'no-reply@shopsys.com',
                    self::KEY_CUSTOMER_USER_DATA_PASSWORD => 'user123',
                    self::KEY_CUSTOMER_USER_DATA_TELEPHONE => '605000123',
                ],
                self::KEY_BILLING_ADDRESS => [
                    self::KEY_ADDRESS_COMPANY_CUSTOMER => true,
                    self::KEY_ADDRESS_COMPANY_NAME => 'Shopsys',
                    self::KEY_ADDRESS_COMPANY_NUMBER => '123456',
                    self::KEY_ADDRESS_STREET => 'Hlubinsk??',
                    self::KEY_ADDRESS_CITY => 'Ostrava',
                    self::KEY_ADDRESS_POSTCODE => '70200',
                    self::KEY_ADDRESS_COUNTRY => $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC),
                ],
            ],
            [
                self::KEY_CUSTOMER_USER_DATA => [
                    self::KEY_CUSTOMER_USER_DATA_FIRST_NAME => 'Igor',
                    self::KEY_CUSTOMER_USER_DATA_LAST_NAME => 'Anpilogov',
                    self::KEY_CUSTOMER_USER_DATA_EMAIL => 'no-reply.3@shopsys.com',
                    self::KEY_CUSTOMER_USER_DATA_PASSWORD => 'no-reply.3',
                ],
                self::KEY_BILLING_ADDRESS => [
                    self::KEY_ADDRESS_COMPANY_CUSTOMER => false,
                    self::KEY_ADDRESS_CITY => 'Budi??ov nad Budi??ovkou',
                    self::KEY_ADDRESS_COUNTRY => $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC),
                ],
            ],
            [
                self::KEY_CUSTOMER_USER_DATA => [
                    self::KEY_CUSTOMER_USER_DATA_FIRST_NAME => 'Hana',
                    self::KEY_CUSTOMER_USER_DATA_LAST_NAME => 'Anrejsov??',
                    self::KEY_CUSTOMER_USER_DATA_EMAIL => 'no-reply.5@shopsys.com',
                    self::KEY_CUSTOMER_USER_DATA_PASSWORD => 'no-reply.5',
                ],
                self::KEY_BILLING_ADDRESS => [
                    self::KEY_ADDRESS_COMPANY_CUSTOMER => false,
                    self::KEY_ADDRESS_CITY => 'Brno',
                    self::KEY_ADDRESS_COUNTRY => $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC),
                ],
            ],
            [
                self::KEY_CUSTOMER_USER_DATA => [
                    self::KEY_CUSTOMER_USER_DATA_FIRST_NAME => 'Alexandr',
                    self::KEY_CUSTOMER_USER_DATA_LAST_NAME => 'Ton',
                    self::KEY_CUSTOMER_USER_DATA_EMAIL => 'no-reply.9@shopsys.com',
                    self::KEY_CUSTOMER_USER_DATA_PASSWORD => 'no-reply.9',
                    self::KEY_CUSTOMER_USER_DATA_TELEPHONE => '606060606',
                ],
                self::KEY_BILLING_ADDRESS => [
                    self::KEY_ADDRESS_COMPANY_CUSTOMER => false,
                    self::KEY_ADDRESS_CITY => 'Bohum??n',
                    self::KEY_ADDRESS_STREET => 'Na Strzi 3',
                    self::KEY_ADDRESS_POSTCODE => '69084',
                    self::KEY_ADDRESS_COUNTRY => $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC),
                ],
            ],
            [
                self::KEY_CUSTOMER_USER_DATA => [
                    self::KEY_CUSTOMER_USER_DATA_FIRST_NAME => 'Pavel',
                    self::KEY_CUSTOMER_USER_DATA_LAST_NAME => 'Nedv??d',
                    self::KEY_CUSTOMER_USER_DATA_EMAIL => 'no-reply.10@shopsys.com',
                    self::KEY_CUSTOMER_USER_DATA_PASSWORD => 'no-reply.10',
                    self::KEY_CUSTOMER_USER_DATA_TELEPHONE => '606060606',
                ],
                self::KEY_BILLING_ADDRESS => [
                    self::KEY_ADDRESS_COMPANY_CUSTOMER => false,
                    self::KEY_ADDRESS_CITY => 'Tur??n',
                    self::KEY_ADDRESS_STREET => 'Tur??nsk?? 5',
                    self::KEY_ADDRESS_POSTCODE => '12345',
                    self::KEY_ADDRESS_COUNTRY => $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC),
                ],
                self::KEY_DELIVERY_ADDRESS => [
                    self::KEY_ADDRESS_CITY => 'Bahamy',
                    self::KEY_ADDRESS_POSTCODE => '99999',
                    self::KEY_ADDRESS_STREET => 'Bahamsk?? 99',
                    self::KEY_ADDRESS_COUNTRY => $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC),
                ],
            ],
            [
                self::KEY_CUSTOMER_USER_DATA => [
                    self::KEY_CUSTOMER_USER_DATA_FIRST_NAME => 'Rostislav',
                    self::KEY_CUSTOMER_USER_DATA_LAST_NAME => 'V??tek',
                    self::KEY_CUSTOMER_USER_DATA_EMAIL => 'vitek@shopsys.com',
                    self::KEY_CUSTOMER_USER_DATA_PASSWORD => 'user123',
                    self::KEY_CUSTOMER_USER_DATA_TELEPHONE => '606060606',
                ],
                self::KEY_BILLING_ADDRESS => [
                    self::KEY_ADDRESS_COMPANY_CUSTOMER => true,
                    self::KEY_ADDRESS_COMPANY_NAME => 'Shopsys',
                    self::KEY_ADDRESS_CITY => 'Ostrava',
                    self::KEY_ADDRESS_STREET => 'Hlubinsk?? 5',
                    self::KEY_ADDRESS_POSTCODE => '70200',
                    self::KEY_ADDRESS_COUNTRY => $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC),
                ],
                self::KEY_DELIVERY_ADDRESS => [
                    self::KEY_ADDRESS_ADDRESS_FILLED => true,
                    self::KEY_ADDRESS_COMPANY_NAME => 'Rockpoint',
                    self::KEY_ADDRESS_FIRST_NAME => 'Eva',
                    self::KEY_ADDRESS_LAST_NAME => 'Wallicov??',
                    self::KEY_ADDRESS_CITY => 'Ostrava',
                    self::KEY_ADDRESS_POSTCODE => '70030',
                    self::KEY_ADDRESS_STREET => 'Rudn??',
                    self::KEY_ADDRESS_TELEPHONE => '123456789',
                    self::KEY_ADDRESS_COUNTRY => $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC),
                ],
            ],
            [
                self::KEY_CUSTOMER_USER_DATA => [
                    self::KEY_CUSTOMER_USER_DATA_FIRST_NAME => '??ubom??r',
                    self::KEY_CUSTOMER_USER_DATA_LAST_NAME => 'Nov??k',
                    self::KEY_CUSTOMER_USER_DATA_EMAIL => 'no-reply.11@shopsys.com',
                    self::KEY_CUSTOMER_USER_DATA_PASSWORD => 'test123',
                    self::KEY_CUSTOMER_USER_DATA_TELEPHONE => '606060606',
                ],
                self::KEY_BILLING_ADDRESS => [
                    self::KEY_ADDRESS_COMPANY_CUSTOMER => false,
                    self::KEY_ADDRESS_CITY => 'Bratislava',
                    self::KEY_ADDRESS_STREET => 'Brn??nsk??',
                    self::KEY_ADDRESS_POSTCODE => '1010',
                    self::KEY_ADDRESS_COUNTRY => $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA),
                ],
                self::KEY_DELIVERY_ADDRESS => [
                    self::KEY_ADDRESS_ADDRESS_FILLED => true,
                    self::KEY_ADDRESS_COMPANY_NAME => 'Rockpoint',
                    self::KEY_ADDRESS_CITY => 'Bratislava',
                    self::KEY_ADDRESS_POSTCODE => '10100',
                    self::KEY_ADDRESS_STREET => 'Ostravsk?? 55/65A',
                    self::KEY_ADDRESS_TELEPHONE => '758686320',
                    self::KEY_ADDRESS_COUNTRY => $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA),
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getDistinctCustomerUsersDataProvider(): array
    {
        return [
            [
                self::KEY_CUSTOMER_USER_DATA => [
                    self::KEY_CUSTOMER_USER_DATA_FIRST_NAME => 'Jana',
                    self::KEY_CUSTOMER_USER_DATA_LAST_NAME => 'Anov????nov??',
                    self::KEY_CUSTOMER_USER_DATA_EMAIL => 'no-reply.2@shopsys.com',
                    self::KEY_CUSTOMER_USER_DATA_PASSWORD => 'no-reply.2',
                ],
                self::KEY_BILLING_ADDRESS => [
                    self::KEY_ADDRESS_COMPANY_CUSTOMER => false,
                    self::KEY_ADDRESS_CITY => 'A??',
                    self::KEY_ADDRESS_COUNTRY => $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC),
                ],
            ],
            [
                self::KEY_CUSTOMER_USER_DATA => [
                    self::KEY_CUSTOMER_USER_DATA_FIRST_NAME => 'Ida',
                    self::KEY_CUSTOMER_USER_DATA_LAST_NAME => 'Anpilogova',
                    self::KEY_CUSTOMER_USER_DATA_EMAIL => 'no-reply.4@shopsys.com',
                    self::KEY_CUSTOMER_USER_DATA_PASSWORD => 'no-reply.4',
                ],
                self::KEY_BILLING_ADDRESS => [
                    self::KEY_ADDRESS_COMPANY_CUSTOMER => false,
                    self::KEY_ADDRESS_CITY => 'Praha',
                    self::KEY_ADDRESS_COUNTRY => $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC),
                ],
            ],
            [
                self::KEY_CUSTOMER_USER_DATA => [
                    self::KEY_CUSTOMER_USER_DATA_FIRST_NAME => 'Petr',
                    self::KEY_CUSTOMER_USER_DATA_LAST_NAME => 'Anrig',
                    self::KEY_CUSTOMER_USER_DATA_EMAIL => 'no-reply.6@shopsys.com',
                    self::KEY_CUSTOMER_USER_DATA_PASSWORD => 'no-reply.6',
                ],
                self::KEY_BILLING_ADDRESS => [
                    self::KEY_ADDRESS_COMPANY_CUSTOMER => false,
                    self::KEY_ADDRESS_CITY => 'Jesen??k',
                    self::KEY_ADDRESS_COUNTRY => $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC),
                ],
                self::KEY_DELIVERY_ADDRESS => [
                    self::KEY_ADDRESS_ADDRESS_FILLED => true,
                    self::KEY_ADDRESS_CITY => 'Opava',
                    self::KEY_ADDRESS_POSTCODE => '70000',
                    self::KEY_ADDRESS_STREET => 'Ostravsk??',
                    self::KEY_ADDRESS_COUNTRY => $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC),
                ],
            ],
            [
                self::KEY_CUSTOMER_USER_DATA => [
                    self::KEY_CUSTOMER_USER_DATA_FIRST_NAME => 'Silva',
                    self::KEY_CUSTOMER_USER_DATA_LAST_NAME => 'Anrigov??',
                    self::KEY_CUSTOMER_USER_DATA_EMAIL => 'no-reply.7@shopsys.com',
                    self::KEY_CUSTOMER_USER_DATA_PASSWORD => 'no-reply.7',
                ],
                self::KEY_BILLING_ADDRESS => [
                    self::KEY_ADDRESS_COMPANY_CUSTOMER => false,
                    self::KEY_ADDRESS_CITY => 'Ostrava',
                    self::KEY_ADDRESS_COUNTRY => $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC),
                ],
            ],
            [
                self::KEY_CUSTOMER_USER_DATA => [
                    self::KEY_CUSTOMER_USER_DATA_FIRST_NAME => 'Derick',
                    self::KEY_CUSTOMER_USER_DATA_LAST_NAME => 'Ansah',
                    self::KEY_CUSTOMER_USER_DATA_EMAIL => 'no-reply.8@shopsys.com',
                    self::KEY_CUSTOMER_USER_DATA_PASSWORD => 'no-reply.8',
                ],
                self::KEY_BILLING_ADDRESS => [
                    self::KEY_ADDRESS_COMPANY_CUSTOMER => false,
                    self::KEY_ADDRESS_CITY => 'Opava',
                    self::KEY_ADDRESS_COUNTRY => $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC),
                ],
            ],
            [
                self::KEY_CUSTOMER_USER_DATA => [
                    self::KEY_CUSTOMER_USER_DATA_FIRST_NAME => 'Johny',
                    self::KEY_CUSTOMER_USER_DATA_LAST_NAME => 'English',
                    self::KEY_CUSTOMER_USER_DATA_EMAIL => 'no-reply@shopsys.com',
                    self::KEY_CUSTOMER_USER_DATA_PASSWORD => 'user123',
                    self::KEY_CUSTOMER_USER_DATA_TELEPHONE => '603123456',
                ],
                self::KEY_BILLING_ADDRESS => [
                    self::KEY_ADDRESS_COMPANY_CUSTOMER => true,
                    self::KEY_ADDRESS_COMPANY_NAME => 'Shopsys',
                    self::KEY_ADDRESS_CITY => 'Ostrava',
                    self::KEY_ADDRESS_STREET => 'Hlubinsk??',
                    self::KEY_ADDRESS_POSTCODE => '70200',
                    self::KEY_ADDRESS_COUNTRY => $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC),
                ],
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            CountryDataFixture::class,
        ];
    }

    /**
     * @param \App\Model\Customer\User\CustomerUser $customer
     */
    protected function resetPassword(CustomerUser $customer)
    {
        $customer->setResetPasswordHash($this->hashGenerator->generateHash(CustomerUserPasswordFacade::RESET_PASSWORD_HASH_LENGTH));
        $this->em->flush($customer);
    }
}
