<?php

declare(strict_types=1);

namespace App\Model\Product\Availability;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Product\Availability\Availability as BaseAvailability;
use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityData as BaseAvailabilityData;

/**
 * @ORM\Table(name="availabilities")
 * @ORM\Entity
 * @method setTranslations(\App\Model\Product\Availability\AvailabilityData $availabilityData)
 */
class Availability extends BaseAvailability
{
    public const IN_STOCK = 'inStock';
    public const IN_SALE_STOCK = 'inSaleStock';
    public const IN_EXTERNAL_STOCK = 'inExternalStock';
    public const IN_DAYS = 'inDays';
    public const BY_VARIANTS = 'byVariants';
    public const OUT_OF_STOCK = 'outOfStock';

    private const RATING = [
        self::IN_SALE_STOCK,
        self::IN_STOCK,
        self::IN_EXTERNAL_STOCK,
        self::IN_DAYS,
        self::BY_VARIANTS,
        self::OUT_OF_STOCK,
    ];

    /**
     * @ORM\Column(type="string", length=7)
     */
    private string $rgbColor;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $code;

    /**
     * @param \App\Model\Product\Availability\AvailabilityData $availabilityData
     */
    public function __construct(AvailabilityData $availabilityData)
    {
        $this->rgbColor = $availabilityData->rgbColor;
        $this->code = $availabilityData->code;
        parent::__construct($availabilityData);
    }

    /**
     * @param \App\Model\Product\Availability\AvailabilityData $availabilityData
     */
    public function edit(BaseAvailabilityData $availabilityData)
    {
        $this->rgbColor = $availabilityData->rgbColor;
        $this->code = $availabilityData->code;
        parent::edit($availabilityData);
    }

    /**
     * @return string
     */
    public function getRgbColor(): string
    {
        return $this->rgbColor;
    }

    /**
     * @return string
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @return int
     */
    public function getRating(): int
    {
        return array_search($this->code, self::RATING, true);
    }

    /**
     * @return bool
     */
    public function isInDays(): bool
    {
        return $this->code === self::IN_DAYS;
    }
}
