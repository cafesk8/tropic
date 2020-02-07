<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport\PickupPlace;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\ShopBundle\Model\Country\CountryFacade;
use Shopsys\ShopBundle\Model\Transport\TransportFacade;

class PickupPlaceFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    private $entityManager;

    /**
     * @var \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlaceRepository
     */
    private $pickupPlaceRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\Transport\TransportFacade
     */
    private $transportFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $entityManager
     * @param \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlaceRepository $pickupPlaceRepository
     * @param \Shopsys\ShopBundle\Model\Transport\TransportFacade $transportFacade
     * @param \Shopsys\ShopBundle\Model\Country\CountryFacade $countryFacade
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

    /**
     * @param int $id
     * @return \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlace
     */
    public function getById($id): PickupPlace
    {
        return $this->pickupPlaceRepository->getById($id);
    }

    /**
     * @param string|null $searchQuery
     * @param int $transportId
     * @return \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlace[]
     */
    public function findActiveBySearchQueryAndTransportId(?string $searchQuery, int $transportId): array
    {
        $transport = $this->transportFacade->getById($transportId);
        $countryCodesForCurrentDomain = $this->countryFacade->getAllCodesForDomainInArray();
        return $this->pickupPlaceRepository->findActiveBySearchQueryAndTransportType($searchQuery, $transport, $countryCodesForCurrentDomain);
    }

    /**
     * @param int $transportId
     * @return \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlace[]
     */
    public function getAllForTransportId(int $transportId): array
    {
        $transport = $this->transportFacade->getById($transportId);
        $countryCodesForCurrentDomain = $this->countryFacade->getAllCodesForDomainInArray();
        return $this->pickupPlaceRepository->getAllForTransportAndCountryCodes($transport, $countryCodesForCurrentDomain);
    }
}
