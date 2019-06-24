<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode\Grid;

use Shopsys\FrameworkBundle\Model\Order\PromoCode\Grid\PromoCodeGridFactory as BasePromoCodeGridFactory;

class PromoCodeGridFactory extends BasePromoCodeGridFactory
{
    public function create()
    {
        $grid = parent::create();

        $grid->addColumn('usage_limit', 'pc.usageLimit', t('Maximální počet použití'), true);
        $grid->addColumn('unlimited', 'pc.unlimited', t('Neomezený'), true);
        $grid->addEditActionColumn('admin_promocode_edit', ['id' => 'pc.id']);

        return $grid;
    }
}
