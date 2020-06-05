<?php

declare(strict_types=1);

namespace App\Model\WatchDog;

use DateTime;

class WatchDogDataFactory
{
    /**
     * @return \App\Model\WatchDog\WatchDogData
     */
    public function createNew(): WatchDogData
    {
        $watchDogData = $this->create();
        $watchDogData->createdAt = new DateTime();

        return $watchDogData;
    }

    /**
     * @return \App\Model\WatchDog\WatchDogData
     */
    private function create(): WatchDogData
    {
        return new WatchDogData();
    }
}
