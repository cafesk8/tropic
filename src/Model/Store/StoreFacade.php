<?php

declare(strict_types=1);

namespace App\Model\Store;

use App\Model\Product\StoreStock\Transfer\AllCzechStoreStockImportCronModule;
use App\Model\Product\StoreStock\Transfer\AllGermanStoreStockImportCronModule;
use App\Model\Product\StoreStock\Transfer\AllSlovakStoreStockImportCronModule;
use App\Model\Product\StoreStock\Transfer\ChangedStoreStockImportCronModule;
use App\Model\Transfer\TransferFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
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
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Model\Transfer\TransferFacade
     */
    private $transferFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Store\StoreRepository $storeRepository
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \App\Model\Store\StoreFactory $storeFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Transfer\TransferFacade $transferFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        StoreRepository $storeRepository,
        ImageFacade $imageFacade,
        StoreFactory $storeFactory,
        Domain $domain,
        TransferFacade $transferFacade
    ) {
        $this->em = $em;
        $this->storeRepository = $storeRepository;
        $this->imageFacade = $imageFacade;
        $this->storeFactory = $storeFactory;
        $this->domain = $domain;
        $this->transferFacade = $transferFacade;
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
    public function getStoreForDomainAndForStoreListById(int $storeId): Store
    {
        return $this->storeRepository->getStoreForDomainAndForStoreListById($storeId, $this->domain->getId());
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
        $this->resetStockImportCronModules();

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

        // reset transfer only in change
        if ($isStoreFranchiseChanged) {
            $this->resetStockImportCronModules();
        }

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

    private function resetStockImportCronModules(): void
    {
        $this->transferFacade->resetTransferByTransferId(AllCzechStoreStockImportCronModule::TRANSFER_IDENTIFIER);
        $this->transferFacade->resetTransferByTransferId(AllSlovakStoreStockImportCronModule::TRANSFER_IDENTIFIER);
        $this->transferFacade->resetTransferByTransferId(AllGermanStoreStockImportCronModule::TRANSFER_IDENTIFIER);
        $this->transferFacade->resetTransferByTransferId(ChangedStoreStockImportCronModule::TRANSFER_IDENTIFIER);
    }

    /**
     * @param int $storeId
     */
    public function delete(int $storeId): void
    {
        $store = $this->storeRepository->getById($storeId);
        $this->resetStockImportCronModules();

        $this->em->remove($store);
        $this->em->flush();
    }

    /**
     * @param int $domainId
     * @return \App\Model\Store\Store[]
     */
    public function getAllPickupPlacesForDomain(int $domainId): array
    {
        return $this->storeRepository->getAllPickupPlacesForDomain($domainId);
    }

    /**
     * @return string[]
     */
    public function findRegionNamesForStoreList(): array
    {
        return $this->storeRepository->findRegionNamesForStoreList($this->domain->getId());
    }

    /**
     * @return \App\Model\Store\Store[][]
     */
    public function findStoresForStoreListIndexedByRegion(): array
    {
        return $this->storeRepository->findStoresForStoreListIndexedByRegion($this->domain->getId());
    }
}
