<?php

declare(strict_types=1);

namespace App\Component\Router;

use App\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DynamicLegacyUrlRedirectSubscriber
{
    private FriendlyUrlFacade $friendlyUrlFacade;

    private DomainRouter $domainRouter;

    private Domain $domain;

    /**
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Component\Router\DomainRouterFactory $domainRouterFactory
     */
    public function __construct(FriendlyUrlFacade $friendlyUrlFacade, Domain $domain, DomainRouterFactory $domainRouterFactory)
    {
        $this->friendlyUrlFacade = $friendlyUrlFacade;
        $this->domain = $domain;
        $this->domainRouter = $domainRouterFactory->getRouter($this->domain->getId());
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        if ($event->getThrowable() instanceof NotFoundHttpException) {
            $pathInfo = $event->getRequest()->getPathInfo();
            $pathWithoutSlash = substr($pathInfo, 1);

            // https://regex101.com/r/5g2576/1/
            $numberOrNumberWithSlashAtEndPattern = '/([\d]+)+\/?$/';
            $hasUrlNumberOrNumberWithSlashAtEnd = (bool)preg_match($numberOrNumberWithSlashAtEndPattern, $pathWithoutSlash);
            $friendlyUrl = null;
            if ($hasUrlNumberOrNumberWithSlashAtEnd === true) {
                $pathWithoutNumberAtEnd = preg_replace($numberOrNumberWithSlashAtEndPattern, '', $pathWithoutSlash);
                $friendlyUrl = $this->friendlyUrlFacade->findByDomainIdAndSlug($this->domain->getId(), $pathWithoutNumberAtEnd);

                // If friendly url was not found we try to find it without slash at the end
                if ($friendlyUrl === null && substr($pathWithoutNumberAtEnd, -1) === '/') {
                    $pathWithoutNumberAtEndWithoutSlash = rtrim($pathWithoutNumberAtEnd , '/');
                    $friendlyUrl = $this->friendlyUrlFacade->findByDomainIdAndSlug($this->domain->getId(), $pathWithoutNumberAtEndWithoutSlash);
                }
            }

            if ($friendlyUrl !== null) {
                $event->setResponse(new RedirectResponse($this->domainRouter->generateByFriendlyUrl($friendlyUrl), 301));
            }
        }
    }
}
