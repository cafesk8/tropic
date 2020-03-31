<?php

declare(strict_types=1);

namespace App\Model\Order\Status\Grid;

use Shopsys\FrameworkBundle\Model\Order\Status\Grid\OrderStatusGridFactory as BaseOrderStatusGridFactory;

class OrderStatusGridFactory extends BaseOrderStatusGridFactory
{
    public function create()
    {
        $grid = parent::create();
        $grid->addColumn('transferStatus', 'os.transferStatus', t('Stav z IS'), true);
        $grid->addColumn('smsAlertType', 'os.smsAlertType', t('Typ sms alertu'), true);
        $grid->addColumn('activatesGiftCertificates', 'os.activatesGiftCertificates', 'Aktivuje dárkové certifikáty', false);

        return $grid;
    }
}
