<?php

declare(strict_types=1);

namespace App\Component\Router;

use App\Component\Router\PrettyFilterUrl\PrettyFilterUrlRouterFactory;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Domain\Exception\InvalidDomainIdException;
use Shopsys\FrameworkBundle\Component\Router\DomainRouterFactory as BaseDomainRouterFactory;
use Shopsys\FrameworkBundle\Component\Router\Exception\RouterNotResolvedException;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlRouterFactory;
use Shopsys\FrameworkBundle\Component\Router\LocalizedRouterFactory;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;

/**
 * @property \App\Component\Router\DomainRouter[] $routersByDomainId
 */
class DomainRouterFactory extends BaseDomainRouterFactory
{
    private PrettyFilterUrlRouterFactory $prettyFilterUrlRouterFactory;

    /**
     * @var string
     */
    private string $cacheDir;

    /**
     * @param mixed $routerConfiguration
     * @param \Symfony\Component\Config\Loader\LoaderInterface $configLoader
     * @param \Shopsys\FrameworkBundle\Component\Router\LocalizedRouterFactory $localizedRouterFactory
     * @param \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlRouterFactory $friendlyUrlRouterFactory
     * @param \App\Component\Router\PrettyFilterUrl\PrettyFilterUrlRouterFactory $prettyFilterUrlRouterFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     * @param string $cacheDir
     */
    public function __construct(
        $routerConfiguration,
        LoaderInterface $configLoader,
        LocalizedRouterFactory $localizedRouterFactory,
        FriendlyUrlRouterFactory $friendlyUrlRouterFactory,
        PrettyFilterUrlRouterFactory $prettyFilterUrlRouterFactory,
        Domain $domain,
        RequestStack $requestStack,
        string $cacheDir
    ) {
        parent::__construct($routerConfiguration, $configLoader, $localizedRouterFactory, $friendlyUrlRouterFactory, $domain, $requestStack);
        $this->prettyFilterUrlRouterFactory = $prettyFilterUrlRouterFactory;
        $this->cacheDir = $cacheDir;
    }

    /**
     * @param int $domainId
     * @return \App\Component\Router\DomainRouter
     */
    public function getRouter($domainId)
    {
        if (!array_key_exists($domainId, $this->routersByDomainId)) {
            try {
                $domainConfig = $this->domain->getDomainConfigById($domainId);
            } catch (InvalidDomainIdException $exception) {
                throw new RouterNotResolvedException('', $exception);
            }
            $context = $this->getRequestContextByDomainConfig($domainConfig);
            $basicRouter = $this->getBasicRouter($domainConfig);
            $localizedRouter = $this->localizedRouterFactory->getRouter($domainConfig->getLocale(), $context);
            $friendlyUrlRouter = $this->friendlyUrlRouterFactory->createRouter($domainConfig, $context);
            $prettyFilterUrlRouter = $this->prettyFilterUrlRouterFactory->createRouter(
                $domainConfig,
                $context,
                $friendlyUrlRouter->getRouteCollection()
            );
            $this->routersByDomainId[$domainId] = new DomainRouter(
                $context,
                $basicRouter,
                $localizedRouter,
                $friendlyUrlRouter,
                $prettyFilterUrlRouter
            );
        }

        return $this->routersByDomainId[$domainId];
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \Symfony\Component\Routing\Router
     */
    protected function getBasicRouter(DomainConfig $domainConfig)
    {
        return new Router(
            $this->configLoader,
            $this->routerConfiguration,
            [
                'resource_type' => 'service',
                'cache_dir' => $this->cacheDir . '/routing/domain' . $domainConfig->getId(),
            ],
            $this->getRequestContextByDomainConfig($domainConfig)
        );
    }
}
