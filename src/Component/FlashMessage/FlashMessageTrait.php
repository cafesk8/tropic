<?php

declare(strict_types=1);

namespace App\Component\FlashMessage;

use Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessage;
use Shopsys\FrameworkBundle\Component\FlashMessage\FlashMessageTrait as BaseFlashMessageTrait;

trait FlashMessageTrait
{
    use BaseFlashMessageTrait;

    /**
     * @return bool
     */
    public function existAnyErrorOrInfoMessages(): bool
    {
        /** @var \Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface */
        $flashBag = $this->container->get('session')->getFlashBag();

        return $flashBag->has(FlashMessage::KEY_ERROR)
            || $flashBag->has(FlashMessage::KEY_INFO);
    }
}
