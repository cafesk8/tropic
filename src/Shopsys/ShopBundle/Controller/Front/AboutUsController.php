<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Symfony\Component\HttpFoundation\Response;

class AboutUsController extends FrontBaseController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function infoAction(): Response
    {
        return $this->render('@ShopsysShop/Front/Content/AboutUs/info.html.twig');
    }
}
