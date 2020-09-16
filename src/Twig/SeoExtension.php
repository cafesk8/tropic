<?php

declare(strict_types=1);

namespace App\Twig;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Seo\SeoSettingFacade;
use Shopsys\FrameworkBundle\Twig\SeoExtension as BaseSeoExtension;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\TwigFunction;

class SeoExtension extends BaseSeoExtension
{
    private RequestStack $requestStack;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Seo\SeoSettingFacade $seoSettingFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     */
    public function __construct(
        SeoSettingFacade $seoSettingFacade,
        Domain $domain,
        RequestStack $requestStack
    ) {
        parent::__construct($seoSettingFacade, $domain);
        $this->requestStack = $requestStack;
    }

    public function getFunctions()
    {
        $functions = parent::getFunctions();
        $functions[] = new TwigFunction('isUserAgentSeznamBot', [$this, 'isUserAgentSeznamBot']);

        return $functions;
    }

    /**
     * @return bool
     */
    public function isUserAgentSeznamBot(): bool
    {
        $userAgent = $this->requestStack->getMasterRequest()->headers->get('User-Agent');

        return  $userAgent !== null && preg_match('/SeznamBot/i', $userAgent);
    }
}
