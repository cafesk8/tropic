<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\ShopBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Symfony\Component\HttpFoundation\Response;

class BushmanShopRedirectController extends FrontBaseController
{
    /**
     * @var \Shopsys\ShopBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade
     */
    private $friendlyUrlFacade;

    /**
     * @param \Shopsys\ShopBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     */
    public function __construct(FriendlyUrlFacade $friendlyUrlFacade)
    {
        $this->friendlyUrlFacade = $friendlyUrlFacade;
    }

    /**
     * @param string $urlParam
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(string $urlParam): Response
    {
        $friendlyUrl = $this->friendlyUrlFacade->findFriendlyUrlBySlugAndDomainId($urlParam);

        if ($friendlyUrl !== null) {
            return $this->redirectToRoute($friendlyUrl->getRouteName(), ['id' => $friendlyUrl->getEntityId()]);
        }

        return new Response('', 404);
    }
}
