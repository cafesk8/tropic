<?php

declare(strict_types=1);

namespace App\Model\Product\Unit;

use Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade as BaseUnitFacade;

class UnitFacade extends BaseUnitFacade
{
    /**
     * @var \App\Model\Product\Unit\UnitRepository
     */
    protected $unitRepository;

    /**
     * @param string $pohodaName
     * @return \App\Model\Product\Unit\Unit
     */
    public function getByPohodaName(string $pohodaName): Unit
    {
        return $this->unitRepository->getByPohodaName($pohodaName);
    }
}
