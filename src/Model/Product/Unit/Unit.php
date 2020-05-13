<?php

declare(strict_types=1);

namespace App\Model\Product\Unit;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Product\Unit\Unit as BaseUnit;
use Shopsys\FrameworkBundle\Model\Product\Unit\UnitData;

/**
 * @ORM\Table(name="units")
 * @ORM\Entity
 * @method setTranslations(\App\Model\Product\Unit\UnitData $unitData)
 * @method edit(\App\Model\Product\Unit\UnitData $unitData)
 */
class Unit extends BaseUnit
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $pohodaName;

    /**
     * @return string|null
     */
    public function getPohodaName(): ?string
    {
        return $this->pohodaName;
    }

    /**
     * @param \App\Model\Product\Unit\UnitData $unitData
     */
    public function __construct(UnitData $unitData)
    {
        $this->pohodaName = $unitData->pohodaName;
        parent::__construct($unitData);
    }
}
