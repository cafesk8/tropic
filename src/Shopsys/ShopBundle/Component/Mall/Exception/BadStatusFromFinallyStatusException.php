<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\Mall\Exception;

class BadStatusFromFinallyStatusException extends BadStatusException
{
    public function __construct()
    {
        parent::__construct(t('Zkonečných stavů nelze měnit stav objednávky'));
    }
}
