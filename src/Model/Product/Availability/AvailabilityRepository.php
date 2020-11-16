<?php

declare(strict_types=1);

namespace App\Model\Product\Availability;

use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityRepository as BaseAvailabilityRepository;
use Shopsys\FrameworkBundle\Model\Product\Availability\Exception\AvailabilityNotFoundException;

/**
 * @method \App\Model\Product\Availability\Availability|null findById(int $availabilityId)
 * @method \App\Model\Product\Availability\Availability getById(int $availabilityId)
 * @method \App\Model\Product\Availability\Availability[] getAll()
 * @method \App\Model\Product\Availability\Availability[] getAllExceptId(int $availabilityId)
 * @method bool isAvailabilityUsed(\App\Model\Product\Availability\Availability $availability)
 * @method replaceAvailability(\App\Model\Product\Availability\Availability $oldAvailability, \App\Model\Product\Availability\Availability $newAvailability)
 */
class AvailabilityRepository extends BaseAvailabilityRepository
{
    /**
     * @param string $code
     * @return \App\Model\Product\Availability\Availability
     */
    public function getByCode(string $code): Availability
    {
        /** @var \App\Model\Product\Availability\Availability|null $availability */
        $availability = $this->getAvailabilityRepository()->findOneBy(['code' => $code]);

        if ($availability === null) {
            throw new AvailabilityNotFoundException('Availability with code ' . $code . ' was not found.');
        }

        return $availability;
    }
}
