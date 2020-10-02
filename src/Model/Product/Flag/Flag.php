<?php

declare(strict_types=1);

namespace App\Model\Product\Flag;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Shopsys\FrameworkBundle\Component\Grid\Ordering\OrderableEntityInterface;
use Shopsys\FrameworkBundle\Model\Product\Flag\Flag as BaseFlag;
use Shopsys\FrameworkBundle\Model\Product\Flag\FlagData as BaseFlagData;

/**
 * @ORM\Table(name="flags")
 * @ORM\Entity
 *
 * @method \Shopsys\FrameworkBundle\Model\Product\Flag\FlagTranslation translation(?string $locale = null)
 * @method setTranslations(\App\Model\Product\Flag\FlagData $flagData)
 */
class Flag extends BaseFlag implements OrderableEntityInterface
{
    public const POHODA_ID_NEW = 'Novinka';
    public const POHODA_ID_CLEARANCE = 'Doprodej';
    public const POHODA_ID_ACTION = 'Akce';
    public const POHODA_ID_RECOMMENDED = 'Doporucujeme';
    public const POHODA_ID_DISCOUNT = 'Sleva';
    public const POHODA_ID_PREPARATION = 'Priprav';

    /**
     * @var int
     *
     * @Gedmo\SortablePosition
     * @ORM\Column(type="integer", nullable=false)
     */
    private $position;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $pohodaId;

    /**
     * @param \App\Model\Product\Flag\FlagData $flagData
     */
    public function __construct(FlagData $flagData)
    {
        parent::__construct($flagData);
        $this->fillCommonProperties($flagData);
    }

    /**
     * @param \App\Model\Product\Flag\FlagData $flagData
     */
    public function edit(BaseFlagData $flagData)
    {
        parent::edit($flagData);
        $this->fillCommonProperties($flagData);
    }

    /**
     * @param \App\Model\Product\Flag\FlagData $flagData
     */
    private function fillCommonProperties(FlagData $flagData): void
    {
        $this->pohodaId = $flagData->pohodaId;
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
        return $this->pohodaId === self::POHODA_ID_DISCOUNT;
    }

    /**
     * @return bool
     */
    public function isClearance(): bool
    {
        return $this->pohodaId === self::POHODA_ID_CLEARANCE;
    }

    /**
     * @return bool
     */
    public function isNews(): bool
    {
        return $this->pohodaId === self::POHODA_ID_NEW;
    }

    /**
     * @return bool
     */
    public function isSpecial(): bool
    {
        return $this->isSale() || $this->isNews() || $this->isClearance();
    }

    /**
     * @return string|null
     */
    public function getPohodaId(): ?string
    {
        return $this->pohodaId;
    }

    /**
     * @return bool
     */
    public function isRecommended(): bool
    {
        return $this->pohodaId === self::POHODA_ID_RECOMMENDED;
    }
}
