<?php

declare(strict_types=1);

namespace Tests\App\Functional\Model\Administrator;

use DateTime;
use Shopsys\FrameworkBundle\Model\Administrator\AdministratorRepository;
use App\DataFixtures\Demo\AdministratorDataFixture;
use Tests\App\Test\TransactionFunctionalTestCase;

class AdministratorRepositoryTest extends TransactionFunctionalTestCase
{
    public function testGetByValidMultidomainLogin()
    {
        $validMultidomainLoginToken = 'validMultidomainLoginToken';
        $multidomainLoginTokenExpiration = new DateTime('+60 seconds');

        /** @var \App\Model\Administrator\Administrator $administrator */
        $administrator = $this->getReference(AdministratorDataFixture::ADMINISTRATOR);
        /** @var \Shopsys\FrameworkBundle\Model\Administrator\AdministratorRepository $administratorRepository */
        $administratorRepository = $this->getContainer()->get(AdministratorRepository::class);

        $administrator->setMultidomainLoginTokenWithExpiration($validMultidomainLoginToken, $multidomainLoginTokenExpiration);
        $this->getEntityManager()->flush($administrator);

        $administratorFromDb = $administratorRepository->getByValidMultidomainLoginToken($validMultidomainLoginToken);

        $this->assertSame($administrator, $administratorFromDb);
    }

    public function testGetByValidMultidomainLoginTokenInvalidTokenException()
    {
        $validMultidomainLoginToken = 'validMultidomainLoginToken';
        $invalidMultidomainLoginToken = 'invalidMultidomainLoginToken';
        $multidomainLoginTokenExpiration = new DateTime('+60 seconds');

        /** @var \App\Model\Administrator\Administrator $administrator */
        $administrator = $this->getReference(AdministratorDataFixture::ADMINISTRATOR);
        /** @var \Shopsys\FrameworkBundle\Model\Administrator\AdministratorRepository $administratorRepository */
        $administratorRepository = $this->getContainer()->get(AdministratorRepository::class);

        $administrator->setMultidomainLoginTokenWithExpiration($validMultidomainLoginToken, $multidomainLoginTokenExpiration);
        $this->getEntityManager()->flush($administrator);

        $this->expectException('\Shopsys\FrameworkBundle\Model\Administrator\Security\Exception\InvalidTokenException');

        $administratorRepository->getByValidMultidomainLoginToken($invalidMultidomainLoginToken);
    }

    public function testGetByValidMultidomainLoginTokenExpiredTokenException()
    {
        $validMultidomainLoginToken = 'validMultidomainLoginToken';
        $multidomainLoginTokenExpiration = new DateTime('-60 seconds');

        /** @var \App\Model\Administrator\Administrator $administrator */
        $administrator = $this->getReference(AdministratorDataFixture::ADMINISTRATOR);
        /** @var \Shopsys\FrameworkBundle\Model\Administrator\AdministratorRepository $administratorRepository */
        $administratorRepository = $this->getContainer()->get(AdministratorRepository::class);

        $administrator->setMultidomainLoginTokenWithExpiration($validMultidomainLoginToken, $multidomainLoginTokenExpiration);
        $this->getEntityManager()->flush($administrator);

        $this->expectException('\Shopsys\FrameworkBundle\Model\Administrator\Security\Exception\InvalidTokenException');

        $administratorRepository->getByValidMultidomainLoginToken($validMultidomainLoginToken);
    }
}
