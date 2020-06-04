<?php

declare(strict_types=1);

namespace App\Model\Product\Flag;

trait ProductFlagActivityTrait
{
    /**
     * @return bool
     */
    public function isActive(): bool
    {
        $activeToTimestamp = $this->activeTo !== null ? $this->activeTo->getTimestamp() : strtotime('tomorrow');

        return ($this->activeFrom === null || $this->activeFrom->getTimestamp() <= time()) && ($this->activeTo === null || $activeToTimestamp + 86400 > time());
    }
}
