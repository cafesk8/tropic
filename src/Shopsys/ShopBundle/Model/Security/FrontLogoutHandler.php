<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Security;

use Shopsys\FrameworkBundle\Model\Order\OrderFlowFacade;
use Shopsys\FrameworkBundle\Model\Security\FrontLogoutHandler as BaseFrontLogoutHandler;
use Shopsys\ShopBundle\Model\Order\PromoCode\CurrentPromoCodeFacade;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class FrontLogoutHandler extends BaseFrontLogoutHandler
{
    /**
     * @var \Shopsys\ShopBundle\Model\Order\PromoCode\CurrentPromoCodeFacade
     */
    private $currentPromoCodeFacade;

    /**
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderFlowFacade $orderFlowFacade
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\CurrentPromoCodeFacade $currentPromoCodeFacade
     */
    public function __construct(
        RouterInterface $router,
        OrderFlowFacade $orderFlowFacade,
        CurrentPromoCodeFacade $currentPromoCodeFacade
    ) {
        parent::__construct($router, $orderFlowFacade);

        $this->currentPromoCodeFacade = $currentPromoCodeFacade;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function onLogoutSuccess(Request $request)
    {
        $this->currentPromoCodeFacade->removeEnteredPromoCode();

        return parent::onLogoutSuccess($request);
    }
}
