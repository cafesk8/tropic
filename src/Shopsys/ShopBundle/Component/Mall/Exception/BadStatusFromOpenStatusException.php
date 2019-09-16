<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Mall\Exception;

class BadStatusFromOpenStatusException extends BadStatusException
{
    public function __construct()
    {
        parent::__construct(t('Ze stavu obejdnávky Otevřena můžete změnit stav pouze na Doručována nebo Zrušena'));
    }
}
