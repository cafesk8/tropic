<?php

declare(strict_types=1);

namespace App\Model\Product\Availability;

use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityData as BaseAvailabilityData;

class AvailabilityData extends BaseAvailabilityData
{
    public const DEFAULT_COLOR = '#3ea938';

    public string $rgbColor;

    public ?string $code;

    public function __construct()
    {
        parent::__construct();
        $this->rgbColor = self::DEFAULT_COLOR;
        $this->code = null;
    }
}
