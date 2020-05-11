<?php

declare(strict_types=1);

namespace App\Model\Product\Flag;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Shopsys\FrameworkBundle\Component\Grid\Ordering\OrderableEntityInterface;
use Shopsys\FrameworkBundle\Model\Product\Flag\Flag as BaseFlag;
use Shopsys\FrameworkBundle\Model\Product\Flag\FlagData;

/**
 * @ORM\Table(name="flags")
 * @ORM\Entity
 *
 * @method \Shopsys\FrameworkBundle\Model\Product\Flag\FlagTranslation translation(?string $locale = null)
 * @method setTranslations(\App\Model\Product\Flag\FlagData $flagData)
 */
class Flag extends BaseFlag implements OrderableEntityInterface
{
    /**
     * @var int
     *
     * @Gedmo\SortablePosition
     * @ORM\Column(type="integer", nullable=false)
     */
    private $position;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $sale;

    /**
     * @param \App\Model\Product\Flag\FlagData $flagData
     */
    public function __construct(FlagData $flagData)
    {
        parent::__construct($flagData);
        $this->sale = $flagData->sale;
    }

    /**
     * @param \App\Model\Product\Flag\FlagData $flagData
     */
    public function edit(FlagData $flagData)
    {
        parent::edit($flagData);
        $this->sale = $flagData->sale;
    }

    /**
     * @param int $position
     */
    public function setPosition($position): void
    {
        $this->position = $position;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @return bool
     */
    public function isSale(): bool
    {
        return $this->sale;
    }
}
