<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\Order\MassAction;

interface OrderMassAction
{
    /**
     * @param array $selectedOrdersIds
     */
    public function process(array $selectedOrdersIds);
}
