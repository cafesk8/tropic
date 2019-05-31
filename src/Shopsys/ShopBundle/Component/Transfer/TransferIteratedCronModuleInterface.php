<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Transfer;

use Shopsys\Plugin\Cron\IteratedCronModuleInterface;

interface TransferIteratedCronModuleInterface extends IteratedCronModuleInterface
{
    /**
     * This method is called on every start of this cron module . It's not depends on suspending of this module.
     */
    public function isSkipped();

    /**
     * This method is called on every start of this cron module . It's not depends on suspending of this module.
     */
    public function start();

    /**
     * This method is called on every end of this cron module . It's not depends on suspending of this module.
     */
    public function end();
}
