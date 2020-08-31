<?php

declare(strict_types=1);

namespace App\Model\Transport\PickupPlace;

use App\Model\Transport\PickupPlace\Exception\PickupPlaceNotFoundException;
use Symfony\Component\Form\DataTransformerInterface;

class PickupPlaceIdToEntityTransformer implements DataTransformerInterface
{
    /**
     * @var \App\Model\Transport\PickupPlace\PickupPlaceFacade
     */
    private $pickupPlaceFacade;

    /**
     * @param \App\Model\Transport\PickupPlace\PickupPlaceFacade $pickupPlaceFacade
     */
    public function __construct(PickupPlaceFacade $pickupPlaceFacade)
    {
        $this->pickupPlaceFacade = $pickupPlaceFacade;
    }

    /**
     * @var \App\Model\Transport\PickupPlace\PickupPlace
     * @param mixed $pickupPlace
     * @return int|null
     */
    public function transform($pickupPlace): ?int
    {
        if ($pickupPlace instanceof PickupPlace) {
            return $pickupPlace->getId();
        }

        return null;
    }

    /**
     * @var int|null
     * @param mixed $pickupPlaceId
     * @return \App\Model\Transport\PickupPlace\PickupPlace|null
     */
    public function reverseTransform($pickupPlaceId): ?PickupPlace
    {
        if ($pickupPlaceId === null) {
            return null;
        }

        try {
            $pickupPlace = $this->pickupPlaceFacade->getById((int)$pickupPlaceId);
        } catch (PickupPlaceNotFoundException $notFoundException) {
            return null;
        }

        return $pickupPlace;
    }
}
