<?php

declare(strict_types=1);

namespace App\Model\Product\Unit;

use Shopsys\FrameworkBundle\Model\Product\Unit\UnitRepository as BaseUnitRepository;

/**
 * @method \App\Model\Product\Unit\Unit|null findById(int $unitId)
 * @method \App\Model\Product\Unit\Unit getById(int $unitId)
 * @method \App\Model\Product\Unit\Unit[] getAll()
 * @method \App\Model\Product\Unit\Unit[] getAllExceptId(int $unitId)
 * @method bool existsProductWithUnit(\App\Model\Product\Unit\Unit $unit)
 * @method replaceUnit(\App\Model\Product\Unit\Unit $oldUnit, \App\Model\Product\Unit\Unit $newUnit)
 */
class UnitRepository extends BaseUnitRepository
{
    /**
     * @param string $pohodaName
     * @return \App\Model\Product\Unit\Unit
     */
    public function getByPohodaName(string $pohodaName): Unit
    {
        /** @var \App\Model\Product\Unit\Unit|null $unit */
        $unit = $this->getUnitRepository()->findOneBy(['pohodaName' => $pohodaName]);

        if ($unit === null) {
            throw new \Shopsys\FrameworkBundle\Model\Product\Unit\Exception\UnitNotFoundException('Unit with Pohoda name ' . $pohodaName . ' not found.');
        }

        return $unit;
    }
}
