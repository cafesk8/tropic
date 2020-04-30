<?php

declare(strict_types=1);

namespace Tests\App\Test;

use PHPUnit\Framework\TestCase;
use Shopsys\FrameworkBundle\Component\DataFixture\PersistentReferenceFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Environment\EnvironmentType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class FunctionalTestCase extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    private $client;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain|null
     */
    private $domain;

    protected function setUpDomain()
    {
        /** @var \Shopsys\FrameworkBundle\Component\Domain\Domain $domain */
        $this->domain = $this->getContainer()->get(Domain::class);
        $this->domain->switchDomainById(Domain::FIRST_DOMAIN_ID);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDomain();
    }

    /**
     * @var string[]|null
     */
    private static $phpUnitTestCaseProperties = null;

    /**
     * @return string[]
     */
    private static function getPhpUnitTestCaseProperties(): array
    {
        if (self::$phpUnitTestCaseProperties === null) {
            self::$phpUnitTestCaseProperties = [];

            $testCaseReflectionClass = new \ReflectionClass(TestCase::class);
            $properties = $testCaseReflectionClass->getProperties();
            foreach ($properties as $property) {
                self::$phpUnitTestCaseProperties[] = $property->getName();
            }
        }

        return self::$phpUnitTestCaseProperties;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $reflectionClass = new \ReflectionClass($this);
        $properties = $reflectionClass->getProperties();
        $excludedProperties = self::getPhpUnitTestCaseProperties();
        foreach ($properties as $property) {
            if (in_array($property->getName(), $excludedProperties, true) === false) {
                $property->setAccessible(true);
                $property->setValue($this, null);
            }
        }
    }

    /**
     * @param bool $createNew
     * @param string $username
     * @param string $password
     * @param array $kernelOptions
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    protected function findClient(
        $createNew = false,
        $username = null,
        $password = null,
        $kernelOptions = []
    ) {
        $defaultKernelOptions = [
            'environment' => EnvironmentType::TEST,
            'debug' => EnvironmentType::isDebug(EnvironmentType::TEST),
        ];

        $kernelOptions = array_replace($defaultKernelOptions, $kernelOptions);

        if ($createNew) {
            $this->client = $this->createClient($kernelOptions);
            $this->setUpDomain();
        } elseif (!isset($this->client)) {
            $this->client = $this->createClient($kernelOptions);
        }

        if ($username !== null) {
            $this->client->setServerParameters([
                'PHP_AUTH_USER' => $username,
                'PHP_AUTH_PW' => $password,
            ]);
        }

        return $this->client;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected function getContainer()
    {
        return $this->findClient()->getContainer()->get('test.service_container');
    }

    /**
     * @param string $referenceName
     * @return object
     */
    protected function getReference($referenceName)
    {
        /** @var \Shopsys\FrameworkBundle\Component\DataFixture\PersistentReferenceFacade $persistentReferenceFacade */
        $persistentReferenceFacade = $this->getContainer()
            ->get(PersistentReferenceFacade::class);

        return $persistentReferenceFacade->getReference($referenceName);
    }

    /**
     * @param string $referenceName
     * @param int $domainId
     * @return object
     */
    protected function getReferenceForDomain(string $referenceName, int $domainId)
    {
        /** @var \Shopsys\FrameworkBundle\Component\DataFixture\PersistentReferenceFacade $persistentReferenceFacade */
        $persistentReferenceFacade = $this->getContainer()
            ->get(PersistentReferenceFacade::class);

        return $persistentReferenceFacade->getReferenceForDomain($referenceName, $domainId);
    }

    protected function skipTestIfFirstDomainIsNotInEnglish()
    {
        if ($this->getFirstDomainLocale() !== 'en') {
            $this->markTestSkipped('Tests for product searching are run only when the first domain has English locale');
        }
    }

    /**
     * We can use the shorthand here as $this->domain->switchDomainById(1) is called in setUp()
     * @return string
     */
    protected function getFirstDomainLocale(): string
    {
        return $this->domain->getLocale();
    }
}
