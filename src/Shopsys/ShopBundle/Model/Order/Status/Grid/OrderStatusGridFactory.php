<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Status\Grid;

use Shopsys\FrameworkBundle\Model\Order\Status\Grid\OrderStatusGridFactory as BaseOrderStatusGridFactory;

class OrderStatusGridFactory extends BaseOrderStatusGridFactory
{
    public function create()
    {
        $grid = parent::create();
        $grid->addColumn('transferStatus', 'os.transferStatus', t('Stav z IS'), true);

        return $grid;
    }
}
