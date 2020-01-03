<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\ShopBundle\Component\Instagram\Instagram;
use Symfony\Component\Templating\EngineInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class InstagramExtension extends AbstractExtension
{
    private const CACHE_TIME_SECOND = 60 * 60;

    private const CACHE_ID = 'instagramFeedV2';

    /**
     * @var \Symfony\Component\Templating\EngineInterface
     */
    private $templating;

    /**
     * @var \Shopsys\ShopBundle\Component\Instagram\Instagram
     */
    private $instagram;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Symfony\Component\Templating\EngineInterface $templating
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\ShopBundle\Component\Instagram\Instagram $instagram
     */
    public function __construct(EngineInterface $templating, Domain $domain, Instagram $instagram)
    {
        $this->templating = $templating;
        $this->domain = $domain;
        $this->instagram = $instagram;
    }

    /**
     * @return \Twig\TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('instagramFeed', [$this, 'getInstagramFeed'], ['is_safe' => ['html']]),
            new TwigFunction('getInstagramCacheTime', [$this, 'getCacheTime']),
            new TwigFunction('getInstagramCacheId', [$this, 'getCacheId']),
        ];
    }

    /**
     * @return string
     */
    public function getInstagramFeed(): string
    {
        try {
            $instagramTemplateObjects = $this->instagram->getInstagramTemplateObjects($this->domain->getLocale());
            $instagramLink = $this->instagram->getInstagramLink($this->domain->getLocale());
        } catch (\Shopsys\FrameworkBundle\Component\Domain\Exception\NoDomainSelectedException $domainSelectedException) {
            $instagramTemplateObjects = [];
            $instagramLink = '';
        }

        return $this->templating->render('@ShopsysShop/Front/Inline/Instagram/list.html.twig', [
            'instagramTemplateObjects' => $instagramTemplateObjects,
            'instagramLink' => $instagramLink,
        ]);
    }

    /**
     * @return int
     */
    public function getCacheTime(): int
    {
        return self::CACHE_TIME_SECOND;
    }

    /**
     * @return string
     */
    public function getCacheId(): string
    {
        return self::CACHE_ID . $this->domain->getLocale();
    }
}
