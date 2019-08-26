<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Status\Grid;

use Shopsys\FrameworkBundle\Model\Order\Status\Grid\OrderStatusGridFactory as BaseOrderStatusGridFactory;

class OrderStatusGridFactory extends BaseOrderStatusGridFactory
{
    public function create()
    {
        $grid = parent::create();
        $grid->addColumn('transferId', 'os.transferId', t('Id stavu z IS'), true);

        return $grid;
    }
}
