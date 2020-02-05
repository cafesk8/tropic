<?php

declare(strict_types=1);

namespace App\Model\Product\Availability;

use Doctrine\ORM\Mapping as ORM;
use Shopsys\FrameworkBundle\Model\Product\Availability\Availability as BaseAvailability;
use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityData as BaseAvailabilityData;

/**
 * @ORM\Table(name="availabilities")
 * @ORM\Entity
 */
class Availability extends BaseAvailability
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=7)
     */
    protected $rgbColor;

    /**
     * @param \App\Model\Product\Availability\AvailabilityData $availabilityData
     */
    public function __construct(AvailabilityData $availabilityData)
    {
        $this->rgbColor = $availabilityData->rgbColor;
        parent::__construct($availabilityData);
    }

    /**
     * @param \App\Model\Product\Availability\AvailabilityData $availabilityData
     */
    public function edit(BaseAvailabilityData $availabilityData)
    {
        $this->rgbColor = $availabilityData->rgbColor;
        parent::edit($availabilityData);
    }

    /**
     * @return string
     */
    public function getRgbColor(): string
    {
        return $this->rgbColor;
    }
}
