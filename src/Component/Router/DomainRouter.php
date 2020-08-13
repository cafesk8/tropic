<?php

declare(strict_types=1);

namespace App\Component\Router;

use App\Component\Router\PrettyFilterUrl\PrettyFilterUrlRouter;
use Psr\Log\LoggerInterface;
use Shopsys\FrameworkBundle\Component\Router\DomainRouter as BaseDomainRouter;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlRouter;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class DomainRouter extends BaseDomainRouter
{
    /**
     * @param \Symfony\Component\Routing\RequestContext $context
     * @param \Symfony\Component\Routing\RouterInterface $basicRouter
     * @param \Symfony\Component\Routing\RouterInterface $localizedRouter
     * @param \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlRouter $friendlyUrlRouter
     * @param \App\Component\Router\PrettyFilterUrl\PrettyFilterUrlRouter $prettyFilterUrlRouter
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(
        RequestContext $context,
        RouterInterface $basicRouter,
        RouterInterface $localizedRouter,
        FriendlyUrlRouter $friendlyUrlRouter,
        PrettyFilterUrlRouter $prettyFilterUrlRouter,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct($context, $basicRouter, $localizedRouter, $friendlyUrlRouter, $logger);
        $this->add($prettyFilterUrlRouter, 40);
    }
}
