<?php

declare(strict_types=1);

namespace App\Model\Product\Unit;

use Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade as BaseUnitFacade;

/**
 * @property \App\Model\Product\Unit\UnitRepository $unitRepository
 */
class UnitFacade extends BaseUnitFacade
{
    /**
     * @param string $pohodaName
     * @return \App\Model\Product\Unit\Unit
     */
    public function getByPohodaName(string $pohodaName): Unit
    {
        return $this->unitRepository->getByPohodaName($pohodaName);
    }

    /**
     * @param string|null $name
     * @param string $locale
     * @return \App\Model\Product\Unit\Unit
     */
    public function getByNameAndLocale(?string $name, string $locale): Unit
    {
        return $this->unitRepository->getByNameAndLocale($name, $locale);
    }
}
