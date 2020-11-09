<?php

declare(strict_types=1);

namespace App\Model\Product\Unit;

use Doctrine\ORM\Query\Expr\Join;
use Shopsys\FrameworkBundle\Model\Product\Unit\Exception\UnitNotFoundException;
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
            throw new UnitNotFoundException('Unit with Pohoda name ' . $pohodaName . ' not found.');
        }

        return $unit;
    }

    /**
     * @param string|null $name
     * @param string $locale
     * @return \App\Model\Product\Unit\Unit
     */
    public function getByNameAndLocale(?string $name, string $locale): Unit
    {
        $units = $this->getUnitRepository()->createQueryBuilder('u')
            ->select('u')
            ->join('u.translations', 'ut', Join::WITH, 'ut.locale = :locale AND ut.name = :name')
            ->setParameters(['name' => $name, 'locale' => $locale])
            ->getQuery()->getResult();

        if (count($units) === 0) {
            throw new UnitNotFoundException('Unit with name ' . $name . ' not found for locale ' . $locale . '.');
        }

        return $units[0];
    }
}
