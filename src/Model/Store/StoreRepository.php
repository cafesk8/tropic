<?php

declare(strict_types=1);

namespace App\Model\Store;

use App\Model\Store\Exception\StoreNotFoundException;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class StoreRepository
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    private function getStoreRepository(): EntityRepository
    {
        return $this->em->getRepository(Store::class);
    }

    /**
     * @param int $storeId
     * @return \App\Model\Store\Store|null
     */
    public function findById($storeId): ?Store
    {
        return $this->getStoreRepository()->find($storeId);
    }

    /**
     * @param int $storeId
     * @return \App\Model\Store\Store
     */
    public function getById($storeId): Store
    {
        $store = $this->findById($storeId);

        if ($store === null) {
            throw new StoreNotFoundException('Store with ID ' . $storeId . ' not found.');
        }

        return $store;
    }

    /**
     * @param string $externalNumber
     * @return \App\Model\Store\Store|null
     */
    public function findByExternalNumber(string $externalNumber): ?Store
    {
        return $this->getStoreRepository()->findOneBy(['externalNumber' => $externalNumber]);
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAllQueryBuilder(): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(Store::class, 's');
    }

    /**
     * @param int $storeId
     * @return \App\Model\Store\Store
     */
    public function getStoreForStoreListById(int $storeId): Store
    {
        $store = $this->getAllQueryBuilder()
            ->andWhere('s.id = :storeId')
            ->andWhere('s.showOnStoreList = true')
            ->setParameter('storeId', $storeId)
            ->getQuery()->getOneOrNullResult();

        if ($store === null) {
            throw new StoreNotFoundException('Store with ID ' . $storeId . ' not found.');
        }

        return $store;
    }

    /**
     * @return \App\Model\Store\Store[]
     */
    public function getAllPickupPlaces(): array
    {
        $queryBuilder = $this->getAllQueryBuilder();
        $queryBuilder->andWhere('s.pickupPlace = true');
        $queryBuilder->orderBy('s.position, s.name', 'asc');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return \App\Model\Store\Store[]
     */
    public function getAll(): array
    {
        return $this->getStoreRepository()->findAll();
    }

    /**
     * @return string[]
     */
    public function findRegionNamesForStoreList(): array
    {
        $queryBuilder = $this->getAllQueryBuilder();
        $queryBuilder->select('s.region')
            ->addSelect('COUNT(s) as storesCount')
            ->andWhere('s.region IS NOT NULL')
            ->andWhere('s.showOnStoreList = true')
            ->groupBy('s.region');

        $regionsArray = $queryBuilder->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);

        $regionsNameAndCount = [];

        foreach ($regionsArray as $regionData) {
            $regionsNameAndCount[$regionData['region']] = $regionData['region'] . ' (' . $regionData['storesCount'] . ')';
        }

        ksort($regionsNameAndCount);
        return $regionsNameAndCount;
    }

    /**
     * @return \App\Model\Store\Store[][]
     */
    public function findStoresForStoreListIndexedByRegion(): array
    {
        $queryBuilder = $this->getAllQueryBuilder();
        $queryBuilder
            ->andWhere('s.showOnStoreList = true')
            ->orderBy('s.region', 'ASC')
            ->addOrderBy('s.position', 'ASC');

        $stores = $queryBuilder->getQuery()->getResult();

        $storesIndexedByRegion = [];
        $storesWithoutRegion = [];

        /** @var \App\Model\Store\Store $store */
        foreach ($stores as $store) {
            if ($store->getRegion() === null || $store->getRegion() === '') {
                $storesWithoutRegion[] = $store;
            } else {
                $storesIndexedByRegion[$store->getRegion()][] = $store;
            }
        }

        ksort($storesIndexedByRegion);
        $storesIndexedByRegion[''] = $storesWithoutRegion;
        return $storesIndexedByRegion;
    }

    /**
     * @return \App\Model\Store\Store|null
     */
    public function findCentralStore(): ?Store
    {
        return $this->getStoreRepository()->findOneBy(['centralStore' => true]);
    }
}
