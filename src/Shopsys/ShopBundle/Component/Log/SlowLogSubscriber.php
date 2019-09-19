<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Log;

use Shopsys\FrameworkBundle\Component\Log\SlowLogSubscriber as BaseSlowLogSubscriber;
use Symfony\Component\HttpKernel\KernelEvents;

class SlowLogSubscriber extends BaseSlowLogSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['initStartTime', 512],
            KernelEvents::TERMINATE => 'addNotice',
        ];
    }
}
