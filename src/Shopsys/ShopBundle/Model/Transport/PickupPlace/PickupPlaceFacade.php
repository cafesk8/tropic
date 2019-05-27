<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport\PickupPlace;

use Doctrine\ORM\EntityManagerInterface;

class PickupPlaceFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlaceRepository
     */
    private $pickupPlaceRepository;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlaceRepository $pickupPlaceRepository
     */
    public function __construct(EntityManagerInterface $entityManager, PickupPlaceRepository $pickupPlaceRepository)
    {
        $this->entityManager = $entityManager;
        $this->pickupPlaceRepository = $pickupPlaceRepository;
    }

    /**
     * @param string $shipper
     * @param string|null $shipperService
     * @return bool
     */
    public function isFirstDownloadForShipperService(string $shipper, ?string $shipperService): bool
    {
        if ($this->pickupPlaceRepository->getCountOfPickupPlacesForShipper($shipper, $shipperService) > 0) {
            return false;
        }

        return true;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlaceData[] $pickupPlaceDataArray
     */
    public function createFromArray(array $pickupPlaceDataArray): void
    {
        foreach ($pickupPlaceDataArray as $pickupPlaceData) {
            $pickupPlace = new PickupPlace($pickupPlaceData);
            $this->entityManager->persist($pickupPlace);
        }

        $this->entityManager->flush();
    }

    /**
     * @param string $shipper
     * @param string|null $shipperService
     * @param \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlaceData[] $pickupPlaceDataArray
     */
    public function createOrEditForArray(string $shipper, ?string $shipperService, array $pickupPlaceDataArray): void
    {
        if ($this->isFirstDownloadForShipperService($shipper, $shipperService)) {
            $this->createFromArray($pickupPlaceDataArray);
            return;
        }

        foreach ($pickupPlaceDataArray as $pickupPlaceData) {
            $pickupPlace = $this->findByBalikobotId($pickupPlaceData->balikobotId, $pickupPlaceData->balikobotShipper, $pickupPlaceData->balikobotShipperService);

            if ($pickupPlace !== null) {
                $this->edit($pickupPlaceData, $pickupPlace);
            } else {
                $this->create($pickupPlaceData);
            }

            return;
        }
    }

    /**
     * @param string $balikobotId
     * @param string $shipper
     * @param string|null $shipperService
     * @return \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlace|null
     */
    private function findByBalikobotId(string $balikobotId, string $shipper, ?string $shipperService): ?PickupPlace
    {
        return $this->pickupPlaceRepository->findByBalikobotId($balikobotId, $shipper, $shipperService);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlaceData $pickupPlaceData
     */
    private function create(PickupPlaceData $pickupPlaceData): void
    {
        $pickupPlace = new PickupPlace($pickupPlaceData);
        $this->entityManager->persist($pickupPlace);
        $this->entityManager->flush($pickupPlace);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlaceData $pickupPlaceData
     * @param \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlace $pickupPlace
     */
    private function edit(PickupPlaceData $pickupPlaceData, PickupPlace $pickupPlace): void
    {
        $pickupPlace->edit($pickupPlaceData);
        $this->entityManager->flush($pickupPlace);
    }
}
