<?php

declare(strict_types=1);

namespace App\Model\Transport\PickupPlace;

use App\Model\Transport\PickupPlace\Exception\PickupPlaceNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Shopsys\FrameworkBundle\Component\String\DatabaseSearching;
use Shopsys\FrameworkBundle\Model\Transport\Transport;

class PickupPlaceRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getPickupPlaceRepository(): EntityRepository
    {
        return $this->entityManager->getRepository(PickupPlace::class);
    }

    /**
     * @param string $shipper
     * @param string|null $shipperService
     * @return int
     */
    public function getCountOfPickupPlacesForShipper(string $shipper, ?string $shipperService): int
    {
        return $this->getPickupPlaceRepository()->count([
            'balikobotShipper' => $shipper,
            'balikobotShipperService' => $shipperService,
        ]);
    }

    /**
     * @param string $balikobotId
     * @param string $shipper
     * @param string|null $shipperService
     * @return \App\Model\Transport\PickupPlace\PickupPlace|null
     */
    public function findByBalikobotId(string $balikobotId, string $shipper, ?string $shipperService): ?PickupPlace
    {
        return $this->getPickupPlaceRepository()->findOneBy([
            'balikobotId' => $balikobotId,
            'balikobotShipper' => $shipper,
            'balikobotShipperService' => $shipperService,
        ]);
    }

    /**
     * @param int $id
     * @return \App\Model\Transport\PickupPlace\PickupPlace
     */
    public function getById(int $id): PickupPlace
    {
        $pickupPlace = $this->getPickUpPlaceRepository()->find($id);

        if ($pickupPlace === null) {
            $message = sprintf('Pickup place with id `%d` was not found.', $id);
            throw new PickupPlaceNotFoundException($message);
        }

        return $pickupPlace;
    }

    /**
     * @param string|null $searchQuery
     * @param \App\Model\Transport\Transport $transport
     * @param string[] $countryCodes
     * @return \App\Model\Transport\PickupPlace\PickupPlace[]
     */
    public function findActiveBySearchQueryAndTransportType(?string $searchQuery, Transport $transport, array $countryCodes): array
    {
        $pickupPlaceQueryBuilder = $this->getPickUpPlaceRepository()->createQueryBuilder('pp');

        $normalizedPostCode = str_replace(' ', '', $searchQuery);
        $pickupPlaceQueryBuilder->andWhere('NORMALIZE(pp.city) LIKE NORMALIZE(:city)'
            . ' OR NORMALIZE(pp.postCode) LIKE NORMALIZE(:postCode)'
            . ' OR NORMALIZE(pp.street) LIKE NORMALIZE(:street)'
            . ' OR NORMALIZE(pp.name) LIKE NORMALIZE(:name)')
            ->setParameter('city', DatabaseSearching::getLikeSearchString($searchQuery) . '%')
            ->setParameter('postCode', DatabaseSearching::getLikeSearchString($normalizedPostCode) . '%')
            ->setParameter('street', DatabaseSearching::getLikeSearchString($searchQuery) . '%')
            ->setParameter('name', DatabaseSearching::getLikeSearchString($searchQuery) . '%');

        $pickupPlaceQueryBuilder->andWhere('pp.balikobotShipper = :balikobotShipper')
            ->setParameter('balikobotShipper', $transport->getBalikobotShipper());

        if ($transport->getBalikobotShipperService() !== null) {
            $pickupPlaceQueryBuilder->andWhere('pp.balikobotShipperService = :balikobotShipperService')
                ->setParameter('balikobotShipperService', $transport->getBalikobotShipperService());
        }

        $pickupPlaceQueryBuilder->andWhere('pp.countryCode IN (:countryCodes)')
            ->setParameter('countryCodes', $countryCodes);

        return $pickupPlaceQueryBuilder->orderBy(
            'NORMALIZE(pp.name), NORMALIZE(pp.city), NORMALIZE(pp.street), pp.postCode'
        )->getQuery()->execute();
    }

    /**
     * @param \App\Model\Transport\Transport $transport
     * @param string[] $countryCodes
     * @return \App\Model\Transport\PickupPlace\PickupPlace[]
     */
    public function getAllForTransportAndCountryCodes(Transport $transport, array $countryCodes): array
    {
        return $this->getPickupPlaceRepository()->findBy([
            'balikobotShipper' => $transport->getBalikobotShipper(),
            'balikobotShipperService' => $transport->getBalikobotShipperService(),
            'countryCode' => $countryCodes,
        ]);
    }
}
