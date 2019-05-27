<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\Transport\PickupPlace;

use Shopsys\ShopBundle\Model\Transport\PickupPlace\Exception\PickupPlaceNotFoundException;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class PickupPlaceIdToEntityTransformer implements DataTransformerInterface
{
    /**
     * @var \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlaceFacade
     */
    private $pickupPlaceFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlaceFacade $pickupPlaceFacade
     */
    public function __construct(PickupPlaceFacade $pickupPlaceFacade)
    {
        $this->pickupPlaceFacade = $pickupPlaceFacade;
    }

    /**
     * @var \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlace
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
     * @return \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlace|null
     */
    public function reverseTransform($pickupPlaceId): ?PickupPlace
    {
        if ($pickupPlaceId === null) {
            return null;
        }

        try {
            $pickupPlace = $this->pickupPlaceFacade->getById((int)$pickupPlaceId);
        } catch (PickupPlaceNotFoundException $notFoundException) {
            throw new TransformationFailedException('Pickup place not found', null, $notFoundException);
        }

        return $pickupPlace;
    }
}
