<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Mall\Exception;

use Exception;

class BadStatusFromShippingStatusException extends Exception implements MallStatusException
{
    public function __construct()
    {
        parent::__construct(t('Ze stavu obejdnávky Odesílána můžete změnit stav pouze na Odeslána nebo Zrušena'), 0, null);
    }
}
