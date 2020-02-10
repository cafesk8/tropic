<?php

declare(strict_types=1);

namespace App\Model\Transport\PickupPlace;

use App\Model\Country\CountryFacade;
use App\Model\Transport\TransportFacade;
use Doctrine\ORM\EntityManagerInterface;

class PickupPlaceFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    private $entityManager;

    /**
     * @var \App\Model\Transport\PickupPlace\PickupPlaceRepository
     */
    private $pickupPlaceRepository;

    /**
     * @var \App\Model\Transport\TransportFacade
     */
    private $transportFacade;

    /**
     * @var \App\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $entityManager
     * @param \App\Model\Transport\PickupPlace\PickupPlaceRepository $pickupPlaceRepository
     * @param \App\Model\Transport\TransportFacade $transportFacade
     * @param \App\Model\Country\CountryFacade $countryFacade
     */
    public function __construct(EntityManagerInterface $entityManager, PickupPlaceRepository $pickupPlaceRepository, TransportFacade $transportFacade, CountryFacade $countryFacade)
    {
        $this->entityManager = $entityManager;
        $this->pickupPlaceRepository = $pickupPlaceRepository;
        $this->transportFacade = $transportFacade;
        $this->countryFacade = $countryFacade;
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
     * @param \App\Model\Transport\PickupPlace\PickupPlaceData[] $pickupPlaceDataArray
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
     * @param \App\Model\Transport\PickupPlace\PickupPlaceData[] $pickupPlaceDataArray
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
     * @return \App\Model\Transport\PickupPlace\PickupPlace|null
     */
    private function findByBalikobotId(string $balikobotId, string $shipper, ?string $shipperService): ?PickupPlace
    {
        return $this->pickupPlaceRepository->findByBalikobotId($balikobotId, $shipper, $shipperService);
    }

    /**
     * @param \App\Model\Transport\PickupPlace\PickupPlaceData $pickupPlaceData
     */
    private function create(PickupPlaceData $pickupPlaceData): void
    {
        $pickupPlace = new PickupPlace($pickupPlaceData);
        $this->entityManager->persist($pickupPlace);
        $this->entityManager->flush($pickupPlace);
    }

    /**
     * @param \App\Model\Transport\PickupPlace\PickupPlaceData $pickupPlaceData
     * @param \App\Model\Transport\PickupPlace\PickupPlace $pickupPlace
     */
    private function edit(PickupPlaceData $pickupPlaceData, PickupPlace $pickupPlace): void
    {
        $pickupPlace->edit($pickupPlaceData);
        $this->entityManager->flush($pickupPlace);
    }

    /**
     * @param int $id
     * @return \App\Model\Transport\PickupPlace\PickupPlace
     */
    public function getById($id): PickupPlace
    {
        return $this->pickupPlaceRepository->getById($id);
    }

    /**
     * @param string|null $searchQuery
     * @param int $transportId
     * @return \App\Model\Transport\PickupPlace\PickupPlace[]
     */
    public function findActiveBySearchQueryAndTransportId(?string $searchQuery, int $transportId): array
    {
        $transport = $this->transportFacade->getById($transportId);
        $countryCodesForCurrentDomain = $this->countryFacade->getAllCodesForDomainInArray();
        return $this->pickupPlaceRepository->findActiveBySearchQueryAndTransportType($searchQuery, $transport, $countryCodesForCurrentDomain);
    }

    /**
     * @param int $transportId
     * @return \App\Model\Transport\PickupPlace\PickupPlace[]
     */
    public function getAllForTransportId(int $transportId): array
    {
        $transport = $this->transportFacade->getById($transportId);
        $countryCodesForCurrentDomain = $this->countryFacade->getAllCodesForDomainInArray();
        return $this->pickupPlaceRepository->getAllForTransportAndCountryCodes($transport, $countryCodesForCurrentDomain);
    }
}
