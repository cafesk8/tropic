<?php

declare(strict_types=1);

namespace App\Model\Pricing\Group;

use Shopsys\FrameworkBundle\Model\Pricing\Group\Grid\PricingGroupGridFactory as BasePricingGroupGridFactory;

class PricingGroupGridFactory extends BasePricingGroupGridFactory
{
    /**
     * @inheritDoc
     */
    public function create()
    {
        $grid = parent::create();
        $grid->addColumn('discount', 'pg.discount', t('Sleva %'));

        return $grid;
    }
}
