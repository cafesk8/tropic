<?php

declare(strict_types=1);

namespace App\Component\Router\PrettyFilterUrl;

use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlMatcher;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouteCollection;

class PrettyFilterUrlMatcher
{
    private FriendlyUrlMatcher $friendlyUrlMatcher;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlMatcher $friendlyUrlMatcher
     */
    public function __construct(FriendlyUrlMatcher $friendlyUrlMatcher)
    {
        $this->friendlyUrlMatcher = $friendlyUrlMatcher;
    }

    /**
     * @param string $pathinfo
     * @param \Symfony\Component\Routing\RouteCollection $friendlyUrlRouteCollection
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return array
     */
    public function match(string $pathinfo, RouteCollection $friendlyUrlRouteCollection, DomainConfig $domainConfig): array
    {
        if (substr($pathinfo, -1) !== '/') {
            throw new ResourceNotFoundException('Do not match urls that do not end with "/", because it would be duplicate content.');
        }

        $pathinfo = preg_replace('/(\/.*\/)ni\/$/', '$1', $pathinfo);

        return $this->friendlyUrlMatcher->match($pathinfo, $friendlyUrlRouteCollection, $domainConfig);
    }
}
