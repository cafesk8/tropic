<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Router\FriendlyUrl;

use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade as BaseFriendlyUrlFacade;

class FriendlyUrlFacade extends BaseFriendlyUrlFacade
{
    public const MAX_URL_UNIQUE_RESOLVE_ATTEMPT = 300;
}
