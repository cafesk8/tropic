<?php

declare(strict_types=1);

namespace App\Component\Router\PrettyFilterUrl;

use App\Component\Router\FriendlyUrl\FriendlyUrlRepository;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class PrettyFilterUrlRouterFactory
{
    private FriendlyUrlRepository $friendlyUrlRepository;

    private PrettyFilterUrlFacade $prettyFilterUrlFacade;

    /**
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlRepository $friendlyUrlRepository
     * @param \App\Component\Router\PrettyFilterUrl\PrettyFilterUrlFacade $prettyFilterUrlFacade
     */
    public function __construct(
        FriendlyUrlRepository $friendlyUrlRepository,
        PrettyFilterUrlFacade $prettyFilterUrlFacade
    ) {
        $this->friendlyUrlRepository = $friendlyUrlRepository;
        $this->prettyFilterUrlFacade = $prettyFilterUrlFacade;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param \Symfony\Component\Routing\RequestContext $context
     * @param \Symfony\Component\Routing\RouteCollection $friendlyUrlRouteCollection
     * @return \App\Component\Router\PrettyFilterUrl\PrettyFilterUrlRouter
     */
    public function createRouter(DomainConfig $domainConfig, RequestContext $context, RouteCollection $friendlyUrlRouteCollection): PrettyFilterUrlRouter
    {
        return new PrettyFilterUrlRouter(
            $domainConfig,
            new PrettyFilterUrlGenerator($context, $this->friendlyUrlRepository, $this->prettyFilterUrlFacade),
            new PrettyFilterUrlMatcher(new FriendlyUrlMatcher($this->friendlyUrlRepository)),
            $context,
            $friendlyUrlRouteCollection
        );
    }
}
