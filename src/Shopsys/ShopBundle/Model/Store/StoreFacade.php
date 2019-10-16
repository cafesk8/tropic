<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Store;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\ShopBundle\Model\Product\StoreStock\Transfer\StoreStockImportCronModule;
use Shopsys\ShopBundle\Model\Transfer\TransferFacade;

class StoreFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Shopsys\ShopBundle\Model\Store\StoreRepository
     */
    private $storeRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Image\ImageFacade
     */
    private $imageFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Store\StoreFactory
     */
    private $storeFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\ShopBundle\Model\Transfer\TransferFacade
     */
    private $transferFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Model\Store\StoreRepository $storeRepository
     * @param \Shopsys\FrameworkBundle\Component\Image\ImageFacade $imageFacade
     * @param \Shopsys\ShopBundle\Model\Store\StoreFactory $storeFactory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\ShopBundle\Model\Transfer\TransferFacade $transferFacade
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
     * @return \Shopsys\ShopBundle\Model\Store\Store[]
     */
    public function getAll(): array
    {
        return $this->storeRepository->getAll();
    }

    /**
     * @param int $storeId
     * @return \Shopsys\ShopBundle\Model\Store\Store|null
     */
    public function findById($storeId): ?Store
    {
        return $this->storeRepository->findById($storeId);
    }

    /**
     * @param int $storeId
     * @return \Shopsys\ShopBundle\Model\Store\Store
     */
    public function getById($storeId): Store
    {
        return $this->storeRepository->getById($storeId);
    }

    /**
     * @param int $storeId
     * @return \Shopsys\ShopBundle\Model\Store\Store
     */
    public function getStoreForDomainAndForStoreListById(int $storeId): Store
    {
        return $this->storeRepository->getStoreForDomainAndForStoreListById($storeId, $this->domain->getId());
    }

    /**
     * @param string $externalNumber
     * @return \Shopsys\ShopBundle\Model\Store\Store|null
     */
    public function findByExternalNumber(string $externalNumber): ?Store
    {
        return $this->storeRepository->findByExternalNumber($externalNumber);
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Store\Store|null
     */
    public function findCentralStore(): ?Store
    {
        return $this->storeRepository->findCentralStore();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Store\StoreData $storeData
     * @return \Shopsys\ShopBundle\Model\Store\Store
     */
    public function create(StoreData $storeData): Store
    {
        $store = $this->storeFactory->create($storeData);

        $this->em->persist($store);
        $this->em->flush();

        $this->uploadImage($store, $storeData);
        $this->resetStockImportCronModule();

        return $store;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Store\Store $store
     * @param \Shopsys\ShopBundle\Model\Store\StoreData $storeData
     * @return \Shopsys\ShopBundle\Model\Store\Store
     */
    public function edit(Store $store, StoreData $storeData): Store
    {
        $isStoreFranchiseChanged = $store->isFranchisor() !== $storeData->franchisor;
        $store->edit($storeData);
        $this->uploadImage($store, $storeData);

        // reset transfer only in change
        if ($isStoreFranchiseChanged) {
            $this->resetStockImportCronModule();
        }

        return $store;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Store\Store $store
     * @param \Shopsys\ShopBundle\Model\Store\StoreData $storeData
     */
    private function uploadImage(Store $store, StoreData $storeData): void
    {
        $this->imageFacade->uploadImage($store, $storeData->images->uploadedFiles, null);
        $this->em->flush();
    }

    private function resetStockImportCronModule(): void
    {
        $this->transferFacade->resetTransferByTransferId(StoreStockImportCronModule::TRANSFER_IDENTIFIER);
    }

    /**
     * @param int $storeId
     */
    public function delete(int $storeId): void
    {
        $store = $this->storeRepository->getById($storeId);
        $this->resetStockImportCronModule();

        $this->em->remove($store);
        $this->em->flush();
    }

    /**
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Store\Store[]
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
     * @return \Shopsys\ShopBundle\Model\Store\Store[][]
     */
    public function findStoresForStoreListIndexedByRegion(): array
    {
        return $this->storeRepository->findStoresForStoreListIndexedByRegion($this->domain->getId());
    }
}
