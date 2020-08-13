<?php

declare(strict_types=1);

namespace App\Component\Router\PrettyFilterUrl;

use App\Form\Front\Product\ProductFilterFormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class PrettyFilterUrlListener
{
    private PrettyFilterUrlFacade $prettyFilterUrlFacade;

    /**
     * @param \App\Component\Router\PrettyFilterUrl\PrettyFilterUrlFacade $prettyFilterUrlFacade
     */
    public function __construct(PrettyFilterUrlFacade $prettyFilterUrlFacade)
    {
        $this->prettyFilterUrlFacade = $prettyFilterUrlFacade;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        if (!$event->isMasterRequest()) {
            return;
        }

        $this->processBrandFilters($request);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    private function processBrandFilters(Request $request): void
    {
        $params = $request->query->all();

        if (isset($params[PrettyFilterUrlRouter::BRAND_PARAM_NAME])) {
            $params[ProductFilterFormType::NAME][ProductFilterFormType::FIELD_BRANDS] =
                $this->prettyFilterUrlFacade->getBrandIdsBySlugs(explode(',', $params[PrettyFilterUrlRouter::BRAND_PARAM_NAME]));
            unset($params[PrettyFilterUrlRouter::BRAND_PARAM_NAME]);
            $request->query->replace($params);
        }
    }
}
