<?php

declare(strict_types=1);

namespace App\Model\Store;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;

class StoreFacade
{
    private const INTERNAL_STOCK_POHODA_NAME = 'TROPIC';

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \App\Model\Store\StoreRepository
     */
    private $storeRepository;

    /**
     * @var \App\Component\Image\ImageFacade
     */
    private $imageFacade;

    /**
     * @var \App\Model\Store\StoreFactory
     */
    private $storeFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Store\StoreRepository $storeRepository
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \App\Model\Store\StoreFactory $storeFactory
     */
    public function __construct(
        EntityManagerInterface $em,
        StoreRepository $storeRepository,
        ImageFacade $imageFacade,
        StoreFactory $storeFactory
    ) {
        $this->em = $em;
        $this->storeRepository = $storeRepository;
        $this->imageFacade = $imageFacade;
        $this->storeFactory = $storeFactory;
    }

    /**
     * @return \App\Model\Store\Store[]
     */
    public function getAll(): array
    {
        return $this->storeRepository->getAll();
    }

    /**
     * @param int $storeId
     * @return \App\Model\Store\Store|null
     */
    public function findById($storeId): ?Store
    {
        return $this->storeRepository->findById($storeId);
    }

    /**
     * @param int $storeId
     * @return \App\Model\Store\Store
     */
    public function getById($storeId): Store
    {
        return $this->storeRepository->getById($storeId);
    }

    /**
     * @param int $storeId
     * @return \App\Model\Store\Store
     */
    public function getStoreForStoreListById(int $storeId): Store
    {
        return $this->storeRepository->getStoreForStoreListById($storeId);
    }

    /**
     * @param string $externalNumber
     * @return \App\Model\Store\Store|null
     */
    public function findByExternalNumber(string $externalNumber): ?Store
    {
        return $this->storeRepository->findByExternalNumber($externalNumber);
    }

    /**
     * @return \App\Model\Store\Store|null
     */
    public function findCentralStore(): ?Store
    {
        return $this->storeRepository->findCentralStore();
    }

    /**
     * @param \App\Model\Store\StoreData $storeData
     * @return \App\Model\Store\Store
     */
    public function create(StoreData $storeData): Store
    {
        $store = $this->storeFactory->create($storeData);

        $this->em->persist($store);
        $this->em->flush();

        $this->uploadImage($store, $storeData);

        return $store;
    }

    /**
     * @param \App\Model\Store\Store $store
     * @param \App\Model\Store\StoreData $storeData
     * @return \App\Model\Store\Store
     */
    public function edit(Store $store, StoreData $storeData): Store
    {
        $store->edit($storeData);
        $this->uploadImage($store, $storeData);

        return $store;
    }

    /**
     * @param \App\Model\Store\Store $store
     * @param \App\Model\Store\StoreData $storeData
     */
    private function uploadImage(Store $store, StoreData $storeData): void
    {
        $this->imageFacade->manageImages($store, $storeData->images, null);
        $this->em->flush();
    }

    /**
     * @param int $storeId
     */
    public function delete(int $storeId): void
    {
        $store = $this->storeRepository->getById($storeId);

        $this->em->remove($store);
        $this->em->flush();
    }

    /**
     * @return \App\Model\Store\Store[]
     */
    public function getAllPickupPlaces(): array
    {
        return $this->storeRepository->getAllPickupPlaces();
    }

    /**
     * @return string[]
     */
    public function findRegionNamesForStoreList(): array
    {
        return $this->storeRepository->findRegionNamesForStoreList();
    }

    /**
     * @return \App\Model\Store\Store[][]
     */
    public function findStoresForStoreListIndexedByRegion(): array
    {
        return $this->storeRepository->findStoresForStoreListIndexedByRegion();
    }

    /**
     * @return \App\Model\Store\Store[]
     */
    public function getAllSaleStocks(): array
    {
        return $this->storeRepository->getAllSaleStocks();
    }

    /**
     * @param string $pohodaName
     * @return \App\Model\Store\Store
     */
    public function getByPohodaName(string $pohodaName): Store
    {
        return $this->storeRepository->getByPohodaName($pohodaName);
    }

    /**
     * @return int[]
     */
    public function getSaleStockExternalNumbersOrderedByPriority(): array
    {
        return [
            (int)$this->getByPohodaName(Store::POHODA_STOCK_SALE_NAME)->getExternalNumber(),
            (int)$this->getByPohodaName(Store::POHODA_STOCK_STORE_SALE_NAME)->getExternalNumber(),
        ];
    }

    /**
     * @return int
     */
    public function getPohodaStockSaleExternalNumber(): int
    {
        return (int)$this->getByPohodaName(Store::POHODA_STOCK_SALE_NAME)->getExternalNumber();
    }

    /**
     * @return int
     */
    public function getPohodaStockStoreExternalNumber(): int
    {
        return (int)$this->getByPohodaName(Store::POHODA_STOCK_STORE_NAME)->getExternalNumber();
    }

    /**
     * @return int
     */
    public function getPohodaStockTropicExternalNumber(): int
    {
        return (int)$this->getByPohodaName(Store::POHODA_STOCK_TROPIC_NAME)->getExternalNumber();
    }

    /**
     * @return int
     */
    public function getPohodaStockStoreSaleExternalNumber(): int
    {
        return (int)$this->getByPohodaName(Store::POHODA_STOCK_STORE_SALE_NAME)->getExternalNumber();
    }

    /**
     * @return int
     */
    public function getPohodaStockExternalExternalNumber(): int
    {
        return (int)$this->getByPohodaName(Store::POHODA_STOCK_EXTERNAL_NAME)->getExternalNumber();
    }

    /**
     * @return int
     */
    public function getDefaultPohodaStockExternalNumber(): int
    {
        return $this->getPohodaStockTropicExternalNumber();
    }

    /**
     * @return int[]
     */
    public function getProductStockExternalNumbers(): array
    {
        return [
            $this->getPohodaStockSaleExternalNumber(),
            $this->getPohodaStockStoreExternalNumber(),
            $this->getPohodaStockTropicExternalNumber(),
            $this->getPohodaStockStoreSaleExternalNumber(),
        ];
    }

    /**
     * @return \App\Model\Store\Store|null
     */
    public function findInternalStock(): ?Store
    {
        return $this->storeRepository->findByPohodaName(self::INTERNAL_STOCK_POHODA_NAME);
    }
}
