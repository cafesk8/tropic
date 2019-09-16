<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Mall\Exception;

class BadStatusFromShippedStatusException extends BadStatusException
{
    public function __construct()
    {
        parent::__construct(t('Ze stavu obejdnávky Odeslána můžete změnit stav pouze na Nedoručena'));
    }
}
