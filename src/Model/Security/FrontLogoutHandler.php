<?php

declare(strict_types=1);

namespace App\Model\Security;

use App\Model\Order\Preview\OrderPreviewSessionFacade;
use App\Model\Order\PromoCode\CurrentPromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Order\OrderFlowFacade;
use Shopsys\FrameworkBundle\Model\Security\FrontLogoutHandler as BaseFrontLogoutHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class FrontLogoutHandler extends BaseFrontLogoutHandler
{
    private CurrentPromoCodeFacade $currentPromoCodeFacade;

    private OrderPreviewSessionFacade $orderPreviewSessionFacade;

    /**
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderFlowFacade $orderFlowFacade
     * @param \App\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     * @param \App\Model\Order\Preview\OrderPreviewSessionFacade $orderPreviewSessionFacade
     */
    public function __construct(
        RouterInterface $router,
        OrderFlowFacade $orderFlowFacade,
        CurrentPromoCodeFacade $currentPromoCodeFacade,
        OrderPreviewSessionFacade $orderPreviewSessionFacade
    ) {
        parent::__construct($router, $orderFlowFacade);

        $this->currentPromoCodeFacade = $currentPromoCodeFacade;
        $this->orderPreviewSessionFacade = $orderPreviewSessionFacade;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function onLogoutSuccess(Request $request): RedirectResponse
    {
        $this->currentPromoCodeFacade->removeEnteredPromoCode();
        $this->orderPreviewSessionFacade->unsetOrderPreviewInfoFromSession();

        return parent::onLogoutSuccess($request);
    }
}
