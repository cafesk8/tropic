<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Gtm;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class GtmListener
{
    /**
     * @var \Shopsys\ShopBundle\Model\Gtm\GtmFacade
     */
    private $gtmFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Gtm\GtmFacade $gtmFacade
     */
    public function __construct(GtmFacade $gtmFacade)
    {
        $this->gtmFacade = $gtmFacade;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $routeName = $event->getRequest()->get('_route');
        if (!$this->isFrontRoute($routeName)) {
            return;
        }

        $this->gtmFacade->onAllFrontPages($routeName);
    }

    /**
     * @param string $routeName
     * @return bool
     */
    private function isFrontRoute(string $routeName): bool
    {
        return strpos($routeName, 'front_') === 0;
    }
}
