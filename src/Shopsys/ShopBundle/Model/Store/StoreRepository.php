<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Store;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Shopsys\ShopBundle\Model\Store\Exception\StoreNotFoundException;

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
     * @return \Shopsys\ShopBundle\Model\Store\Store|null
     */
    public function findById($storeId): ?Store
    {
        return $this->getStoreRepository()->find($storeId);
    }

    /**
     * @param int $storeId
     * @return \Shopsys\ShopBundle\Model\Store\Store
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
     * @return \Shopsys\ShopBundle\Model\Store\Store|null
     */
    public function findByExternalNumber(string $externalNumber): ?Store
    {
        return $this->getStoreRepository()->findOneBy(['externalNumber' => $externalNumber]);
    }

    /**
     * @param int $domainId
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAllForDomainQueryBuilder(int $domainId): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('s')
            ->from(Store::class, 's')
            ->where('s.domainId = :domainId')
            ->setParameter('domainId', $domainId);
    }

    /**
     * @param int $storeId
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Store\Store
     */
    public function getStoreForDomainAndForStoreListById(int $storeId, int $domainId): Store
    {
        $store = $this->getAllForDomainQueryBuilder($domainId)
            ->andWhere('s.id = :storeId')
            ->andWhere('s.showOnStoreList = true')
            ->setParameter('storeId', $storeId)
            ->getQuery()->getOneOrNullResult();

        if ($store === null) {
            throw new StoreNotFoundException('Store with ID ' . $storeId . ' not found for domain with ID `' . $storeId . '`.');
        }

        return $store;
    }

    /**
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Store\Store[]
     */
    public function getAllPickupPlacesForDomain(int $domainId): array
    {
        $queryBuilder = $this->getAllForDomainQueryBuilder($domainId);
        $queryBuilder->andWhere('s.pickupPlace = true');

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Store\Store[]
     */
    public function getAll(): array
    {
        return $this->getStoreRepository()->findAll();
    }

    /**
     * @param int $domainId
     * @return string[]
     */
    public function findRegionNamesForStoreList(int $domainId): array
    {
        $queryBuilder = $this->getAllForDomainQueryBuilder($domainId);
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
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Store\Store[][]
     */
    public function findStoresForStoreListIndexedByRegion(int $domainId): array
    {
        $queryBuilder = $this->getAllForDomainQueryBuilder($domainId);
        $queryBuilder
            ->andWhere('s.showOnStoreList = true')
            ->orderBy('s.region', 'ASC')
            ->addOrderBy('s.position', 'ASC');

        $stores = $queryBuilder->getQuery()->getResult();

        $storesIndexedByRegion = [];
        $storesWithoutRegion = [];

        /** @var \Shopsys\ShopBundle\Model\Store\Store $store */
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
     * @return \Shopsys\ShopBundle\Model\Store\Store|null
     */
    public function findCentralStore(): ?Store
    {
        return $this->getStoreRepository()->findOneBy(['centralStore' => true]);
    }
}
