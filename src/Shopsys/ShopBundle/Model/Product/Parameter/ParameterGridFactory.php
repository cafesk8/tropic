<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Parameter;

use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterGridFactory as BaseParameterGridFactory;

class ParameterGridFactory extends BaseParameterGridFactory
{
    /**
     * @return \Shopsys\FrameworkBundle\Component\Grid\Grid
     */
    public function create()
    {
        $grid = parent::create();

        $grid->addColumn('visibleOnFrontend', 'p.visibleOnFrontend', t('Zobrazit na frontendu'), true);

        return $grid;
    }
}
