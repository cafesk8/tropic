<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Store;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;

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
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Model\Store\StoreRepository $storeRepository
     * @param \Shopsys\FrameworkBundle\Component\Image\ImageFacade $imageFacade
     * @param \Shopsys\ShopBundle\Model\Store\StoreFactory $storeFactory
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
     * @param \Shopsys\ShopBundle\Model\Store\StoreData $storeData
     * @return \Shopsys\ShopBundle\Model\Store\Store
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
     * @param \Shopsys\ShopBundle\Model\Store\Store $store
     * @param \Shopsys\ShopBundle\Model\Store\StoreData $storeData
     * @return \Shopsys\ShopBundle\Model\Store\Store
     */
    public function edit(Store $store, StoreData $storeData): Store
    {
        $store->edit($storeData);
        $this->uploadImage($store, $storeData);

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

    /**
     * @param int $storeId
     */
    public function delete(int $storeId): void
    {
        $store = $this->storeRepository->getById($storeId);

        $this->em->remove($store);
        $this->em->flush();
    }
}
