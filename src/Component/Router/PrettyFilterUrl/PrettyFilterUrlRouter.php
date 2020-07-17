<?php

declare(strict_types=1);

namespace App\Component\Router\PrettyFilterUrl;

use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

class PrettyFilterUrlRouter implements RouterInterface
{
    public const FRIENDLY_URL_SUPPORTED_ROUTES = [
        'front_product_list',
        'front_brand_detail',
    ];

    private DomainConfig $domainConfig;

    private PrettyFilterUrlGenerator $prettyFilterUrlGenerator;

    private PrettyFilterUrlMatcher $prettyFilterUrlMatcher;

    private RequestContext $context;

    private RouteCollection $friendlyUrlRouteCollection;

    private RouteCollection $routeCollection;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param \App\Component\Router\PrettyFilterUrl\PrettyFilterUrlGenerator $prettyFilterUrlGenerator
     * @param \App\Component\Router\PrettyFilterUrl\PrettyFilterUrlMatcher $prettyFilterUrlMatcher
     * @param \Symfony\Component\Routing\RequestContext $context
     * @param \Symfony\Component\Routing\RouteCollection $friendlyUrlRouteCollection
     */
    public function __construct(
        DomainConfig $domainConfig,
        PrettyFilterUrlGenerator $prettyFilterUrlGenerator,
        PrettyFilterUrlMatcher $prettyFilterUrlMatcher,
        RequestContext $context,
        RouteCollection $friendlyUrlRouteCollection
    ) {
        $this->domainConfig = $domainConfig;
        $this->prettyFilterUrlGenerator = $prettyFilterUrlGenerator;
        $this->prettyFilterUrlMatcher = $prettyFilterUrlMatcher;
        $this->context = $context;
        $this->friendlyUrlRouteCollection = $friendlyUrlRouteCollection;
        $this->routeCollection = new RouteCollection();
    }

    /**
     * @inheritDoc
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        return $this->prettyFilterUrlGenerator->generateFromRouteCollection(
            $this->friendlyUrlRouteCollection,
            $this->domainConfig,
            $name,
            $parameters,
            $referenceType
        );
    }

    /**
     * @inheritDoc
     */
    public function match($pathinfo)
    {
        return $this->prettyFilterUrlMatcher->match($pathinfo, $this->friendlyUrlRouteCollection, $this->domainConfig);
    }

    /**
     * @return \Symfony\Component\Routing\RequestContext
     */
    public function getContext(): RequestContext
    {
        return $this->context;
    }

    /**
     * @param \Symfony\Component\Routing\RequestContext $context
     */
    public function setContext(RequestContext $context): void
    {
        $this->context = $context;
    }

    /**
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function getRouteCollection(): RouteCollection
    {
        return $this->routeCollection;
    }
}
