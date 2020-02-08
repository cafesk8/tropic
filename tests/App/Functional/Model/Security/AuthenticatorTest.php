<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Security;

use Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFacade;
use Shopsys\FrameworkBundle\Model\Security\Authenticator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tests\App\Test\FunctionalTestCase;

class AuthenticatorTest extends FunctionalTestCase
{
    public function testSessionIdIsChangedAfterLogin(): void
    {
        /** @var \Shopsys\FrameworkBundle\Model\Security\Authenticator $authenticator */
        $authenticator = $this->getContainer()->get(Authenticator::class);
        /** @var \Shopsys\FrameworkBundle\Model\Customer\User\CustomerUserFacade $customerUserFacade */
        $customerUserFacade = $this->getContainer()->get(CustomerUserFacade::class);

        $customerUser = $customerUserFacade->getCustomerUserById(1);
        $mockedRequest = $this->createMockedRequest();

        $beforeLoginSessionId = $mockedRequest->getSession()->getId();

        $authenticator->loginUser($customerUser, $mockedRequest);

        $afterLoginSessionId = $mockedRequest->getSession()->getId();

        $this->assertNotSame($beforeLoginSessionId, $afterLoginSessionId);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    private function createMockedRequest(): Request
    {
        $request = new Request();

        $session = new Session(new MockArraySessionStorage());
        $session->setId('abc');

        $request->setSession($session);

        return $request;
    }
}
