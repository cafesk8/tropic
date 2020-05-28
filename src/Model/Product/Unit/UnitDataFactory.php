<?php

declare(strict_types=1);

namespace App\Model\Product\Unit;

use Shopsys\FrameworkBundle\Model\Product\Unit\Unit as BaseUnit;
use Shopsys\FrameworkBundle\Model\Product\Unit\UnitData as BaseUnitData;
use Shopsys\FrameworkBundle\Model\Product\Unit\UnitDataFactory as BaseUnitDataFactory;

/**
 * @method \App\Model\Product\Unit\UnitData createFromUnit(\App\Model\Product\Unit\Unit $unit)
 * @method fillNew(\App\Model\Product\Unit\UnitData $unitData)
 * @method \App\Model\Product\Unit\UnitData createInstance()
 */
class UnitDataFactory extends BaseUnitDataFactory
{
    /**
     * @return \App\Model\Product\Unit\UnitData
     */
    public function create(): BaseUnitData
    {
        $unitData = new UnitData();
        $this->fillNew($unitData);

        return $unitData;
    }

    /**
     * @param \App\Model\Product\Unit\UnitData $unitData
     * @param \App\Model\Product\Unit\Unit $unit
     */
    protected function fillFromUnit(BaseUnitData $unitData, BaseUnit $unit): void
    {
        parent::fillFromUnit($unitData, $unit);
        $unitData->pohodaName = $unit->getPohodaName();
    }
}
