<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Controller\Front;

use Symfony\Component\HttpFoundation\Response;

class StyleguideController extends FrontBaseController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(): Response
    {
        return $this->render('@ShopsysShop/Front/Content/Styleguide/index.html.twig');
    }
}
