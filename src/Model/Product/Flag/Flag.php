<?php

declare(strict_types=1);

namespace App\Model\Product\Flag;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Shopsys\FrameworkBundle\Component\Grid\Ordering\OrderableEntityInterface;
use Shopsys\FrameworkBundle\Model\Product\Flag\Flag as BaseFlag;
use Shopsys\FrameworkBundle\Model\Product\Flag\FlagTranslation;

/**
 * @ORM\Table(name="flags")
 * @ORM\Entity
 *
 * @method FlagTranslation translation(?string $locale = null)
 * @method __construct(\App\Model\Product\Flag\FlagData $flagData)
 * @method setTranslations(\App\Model\Product\Flag\FlagData $flagData)
 * @method edit(\App\Model\Product\Flag\FlagData $flagData)
 */
class Flag extends BaseFlag implements OrderableEntityInterface
{
    /**
     * @var int
     *
     * @Gedmo\SortablePosition
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $position;

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
}
