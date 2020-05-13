<?php

declare(strict_types=1);

namespace App\Model\Product\Unit;

use Shopsys\FrameworkBundle\Model\Product\Unit\UnitGridFactory as BaseUnitGridFactory;

class UnitGridFactory extends BaseUnitGridFactory
{
    /**
     * @return \Shopsys\FrameworkBundle\Component\Grid\Grid
     */
    public function create()
    {
        $grid = parent::create();
        $grid->addColumn('pohodaName', 'u.pohodaName', t('Název z Pohody'));

        return $grid;
    }
}
