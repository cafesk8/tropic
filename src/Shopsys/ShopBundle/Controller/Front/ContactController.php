<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

class ContactController extends FrontBaseController
{
    public function indexAction()
    {
        return $this->render('@ShopsysShop/Front/Content/Contact/index.html.twig');
    }
}
