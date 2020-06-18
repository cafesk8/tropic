<?php

declare(strict_types=1);

namespace App\Twig;

use Nette\Utils\Strings;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class UrlExtension extends AbstractExtension
{
    /**
     * @var \App\Component\Router\FriendlyUrl\FriendlyUrlFacade
     */
    private $friendlyUrlFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        FriendlyUrlFacade $friendlyUrlFacade,
        Domain $domain
    ) {
        $this->friendlyUrlFacade = $friendlyUrlFacade;
        $this->domain = $domain;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('webalize', [$this, 'webalize']),
        ];
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('findMainFriendlyUrl', [$this, 'findMainFriendlyUrl']),
        ];
    }

    /**
     * @param string $string
     * @return string
     */
    public function webalize(string $string): string
    {
        return Strings::webalize($string);
    }

    /**
     * @param string $routeName
     * @param int $entityId
     * @return string|null
     */
    public function findMainFriendlyUrl(string $routeName, int $entityId): ?string
    {
        $mainFriendlyUrl = $this->friendlyUrlFacade->findMainFriendlyUrl($this->domain->getId(), $routeName, $entityId);
        if ($mainFriendlyUrl === null) {
            throw new RouteNotFoundException(sprintf('None of the chained routers were able to generate route: %s', $routeName));
        }

        return str_replace('/', '', $mainFriendlyUrl->getSlug());
    }
}
