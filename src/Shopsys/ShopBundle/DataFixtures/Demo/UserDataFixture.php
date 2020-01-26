<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\DataFixtures\Demo;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Generator;
use Shopsys\FrameworkBundle\Component\DataFixture\AbstractReferenceFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\String\HashGenerator;
use Shopsys\FrameworkBundle\Model\Customer\CustomerFacade;
use Shopsys\FrameworkBundle\Model\Customer\CustomerPasswordFacade;
use Shopsys\FrameworkBundle\Model\Customer\User;

class UserDataFixture extends AbstractReferenceFixture implements DependentFixtureInterface
{
    public const USER_WITH_RESET_PASSWORD_HASH = 'user_with_reset_password_hash';

    /** @var \Shopsys\ShopBundle\Model\Customer\CustomerFacade */
    protected $customerFacade;

    /** @var \Shopsys\ShopBundle\DataFixtures\Demo\UserDataFixtureLoader */
    protected $loaderService;

    /** @var \Faker\Generator */
    protected $faker;

    /** @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator */
    protected $em;

    /** @var \Shopsys\ShopBundle\Component\String\HashGenerator */
    protected $hashGenerator;

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\CustomerFacade $customerFacade
     * @param \Shopsys\ShopBundle\DataFixtures\Demo\UserDataFixtureLoader $loaderService
     * @param \Faker\Generator $faker
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
     * @param \Shopsys\ShopBundle\Component\String\HashGenerator $hashGenerator
     */
    public function __construct(
        CustomerFacade $customerFacade,
        UserDataFixtureLoader $loaderService,
        Generator $faker,
        EntityManagerInterface $em,
        HashGenerator $hashGenerator
    ) {
        $this->customerFacade = $customerFacade;
        $this->loaderService = $loaderService;
        $this->faker = $faker;
        $this->em = $em;
        $this->hashGenerator = $hashGenerator;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $countries = [
            $this->getReference(CountryDataFixture::COUNTRY_CZECH_REPUBLIC),
            $this->getReference(CountryDataFixture::COUNTRY_SLOVAKIA),
        ];
        $this->loaderService->injectReferences($countries);

        $customersData = $this->loaderService->getCustomersDataByDomainId(Domain::FIRST_DOMAIN_ID);

        foreach ($customersData as $customerData) {
            /** @var \Shopsys\ShopBundle\Model\Customer\UserData $userData */
            $userData = $customerData->userData;
            $userData->createdAt = $this->faker->dateTimeBetween('-2 week', 'now');
            $userData->pricingGroupUpdatedAt = new \DateTime();
            $customerData->userData = $userData;

            $customer = $this->customerFacade->create($customerData);

            if ($customer->getId() === 1) {
                $this->resetPassword($customer);
                $this->addReference(self::USER_WITH_RESET_PASSWORD_HASH, $customer);
            }
        }
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
     * @param \Shopsys\ShopBundle\Model\Customer\User $customer
     */
    protected function resetPassword(User $customer)
    {
        $customer->setResetPasswordHash($this->hashGenerator->generateHash(CustomerPasswordFacade::RESET_PASSWORD_HASH_LENGTH));
        $this->em->flush($customer);
    }
}
