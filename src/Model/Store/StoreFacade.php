<?php

declare(strict_types=1);

namespace App\Model\Store;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;

class StoreFacade
{
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
        $isStoreFranchiseChanged = $store->isFranchisor() !== $storeData->franchisor;
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
        $this->imageFacade->uploadImage($store, $storeData->images->uploadedFiles, null);
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
}
