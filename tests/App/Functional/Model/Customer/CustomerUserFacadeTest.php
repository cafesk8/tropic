<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Customer;

use App\DataFixtures\Demo\PricingGroupDataFixture;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFacade;
use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateDataFactoryInterface;
use Tests\App\Test\TransactionFunctionalTestCase;

class CustomerUserFacadeTest extends TransactionFunctionalTestCase
{
    protected const EXISTING_EMAIL_ON_DOMAIN_1 = 'no-reply.3@shopsys.com';
    protected const EXISTING_EMAIL_ON_DOMAIN_2 = 'no-reply.4@shopsys.com';

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFacade
     */
    protected $customerUserFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserUpdateDataFactory
     */
    protected $customerUserUpdateDataFactory;

    protected function setUp()
    {
        parent::setUp();
        $this->customerUserFacade = $this->getContainer()->get(CustomerUserFacade::class);
        $this->customerUserUpdateDataFactory = $this->getContainer()->get(CustomerUserUpdateDataFactoryInterface::class);
    }

    public function testChangeEmailToExistingEmailButDifferentDomainDoNotThrowException()
    {
        $customerUser = $this->customerUserFacade->findCustomerUserByEmailAndDomain(self::EXISTING_EMAIL_ON_DOMAIN_1, Domain::FIRST_DOMAIN_ID);
        $customerUserUpdateData = $this->customerUserUpdateDataFactory->createFromCustomerUser($customerUser);
        /** @var \App\Model\Customer\User\CustomerUserData $customerUserData */
        $customerUserData = $customerUserUpdateData->customerUserData;
        $customerUserData->email = self::EXISTING_EMAIL_ON_DOMAIN_2;
        $customerUserUpdateData->customerUserData = $customerUserData;

        $this->customerUserFacade->editByAdmin($customerUser->getId(), $customerUserUpdateData);

        $this->expectNotToPerformAssertions();
    }

    public function testCreateNotDuplicateEmail()
    {
        $customerUserUpdateData = $this->customerUserUpdateDataFactory->create();
        /** @var \App\Model\Customer\User\CustomerUserData $customerUserData */
        $customerUserData = $customerUserUpdateData->customerUserData;
        $customerUserData->pricingGroup = $this->getReferenceForDomain(PricingGroupDataFixture::PRICING_GROUP_BASIC_DOMAIN, 1);
        $customerUserData->domainId = 1;
        $customerUserData->email = 'unique-email@shopsys.com';
        $customerUserData->firstName = 'John';
        $customerUserData->lastName = 'Doe';
        $customerUserData->password = 'password';
        $customerUserUpdateData->customerUserData = $customerUserData;

        $this->customerUserFacade->create($customerUserUpdateData);

        $this->expectNotToPerformAssertions();
    }

    public function testCreateDuplicateEmail()
    {
        $customerUser = $this->customerUserFacade->findCustomerUserByEmailAndDomain(self::EXISTING_EMAIL_ON_DOMAIN_1, 1);
        $customerUserUpdateData = $this->customerUserUpdateDataFactory->createFromCustomerUser($customerUser);
        /** @var \App\Model\Customer\User\CustomerUserData $customerUserData */
        $customerUserData = $customerUserUpdateData->customerUserData;
        $customerUserData->password = 'password';
        $customerUserUpdateData->customerUserData = $customerUserData;
        $this->expectException(\Shopsys\FrameworkBundle\Model\Customer\Exception\DuplicateEmailException::class);

        $this->customerUserFacade->create($customerUserUpdateData);
    }

    public function testCreateDuplicateEmailCaseInsentitive()
    {
        $customerUser = $this->customerUserFacade->findCustomerUserByEmailAndDomain(self::EXISTING_EMAIL_ON_DOMAIN_1, 1);
        $customerUserUpdateData = $this->customerUserUpdateDataFactory->createFromCustomerUser($customerUser);
        /** @var \App\Model\Customer\User\CustomerUserData $customerUserData */
        $customerUserData = $customerUserUpdateData->customerUserData;
        $customerUserData->password = 'password';
        $customerUserData->email = mb_strtoupper(self::EXISTING_EMAIL_ON_DOMAIN_1);
        $customerUserUpdateData->customerUserData = $customerUserData;
        $this->expectException(\Shopsys\FrameworkBundle\Model\Customer\Exception\DuplicateEmailException::class);

        $this->customerUserFacade->create($customerUserUpdateData);
    }
}
