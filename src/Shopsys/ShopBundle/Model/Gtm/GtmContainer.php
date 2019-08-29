<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Gtm;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GtmContainer
{
    /**
     * @var string|null
     */
    private $id;

    /**
     * @var bool
     */
    private $isEnabled;

    /**
     * @var string|null
     */
    private $environment;

    /**
     * @var \Shopsys\ShopBundle\Model\Gtm\DataLayer
     */
    private $dataLayer;

    /**
     * @param array $containersConfigs
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        array $containersConfigs,
        Domain $domain
    ) {
        try {
            $currentLocale = $domain->getLocale();
        } catch (\Shopsys\FrameworkBundle\Component\Domain\Exception\NoDomainSelectedException $e) {
            $currentLocale = $domain->getDomainConfigById(Domain::FIRST_DOMAIN_ID)->getLocale();
        }

        if (!array_key_exists($currentLocale, $containersConfigs)) {
            throw new \InvalidArgumentException(sprintf('Missing GTM configuration for "%s" locale', $currentLocale));
        }

        $config = $containersConfigs[$currentLocale];
        $this->loadConfig($config);
    }

    /**
     * @param array $config
     */
    private function loadConfig(array $config): void
    {
        $configResolver = new OptionsResolver();
        $configResolver
            ->setRequired([
                'enabled',
            ])
            ->setRequired([
                'container_id',
            ])
            ->setRequired([
                'datalayer_locale',
            ])
            ->setDefined([
                'container_environment',
            ])
            ->setAllowedTypes('enabled', ['bool'])
            ->setAllowedTypes('container_id', ['null', 'string'])
            ->setAllowedTypes('datalayer_locale', ['string'])
            ->setAllowedTypes('container_environment', ['null', 'string'])
            ->resolve($config);

        $this->isEnabled = $config['enabled'];
        $this->id = $config['container_id'];
        $this->environment = $config['container_environment'];
        $this->dataLayer = new DataLayer($config['datalayer_locale']);
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    /**
     * @return string|null
     */
    public function getContainerId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getContainerEnvironment(): ?string
    {
        return $this->environment;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Gtm\DataLayer
     */
    public function getDataLayer(): DataLayer
    {
        return $this->dataLayer;
    }
}
