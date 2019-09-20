<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Store;

use Shopsys\FrameworkBundle\Component\Image\ImageFacade;

class StoreDataFactory
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Image\ImageFacade
     */
    private $imageFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Image\ImageFacade $imageFacade
     */
    public function __construct(ImageFacade $imageFacade)
    {
        $this->imageFacade = $imageFacade;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Store\StoreData
     */
    public function create(): StoreData
    {
        return new StoreData();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Store\Store $store
     * @return \Shopsys\ShopBundle\Model\Store\StoreData
     */
    public function createFromStore(Store $store): StoreData
    {
        $storeData = $this->create();
        $this->fillFromStore($storeData, $store);

        return $storeData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Store\StoreData $storeData
     * @param \Shopsys\ShopBundle\Model\Store\Store $store
     */
    private function fillFromStore(StoreData $storeData, Store $store): void
    {
        $storeData->domainId = $store->getDomainId();
        $storeData->name = $store->getName();
        $storeData->description = $store->getDescription();
        $storeData->street = $store->getStreet();
        $storeData->city = $store->getCity();
        $storeData->postcode = $store->getPostcode();
        $storeData->openingHours = $store->getOpeningHours();
        $storeData->googleMapsLink = $store->getGoogleMapsLink();
        $storeData->position = $store->getPosition();
        $storeData->images->orderedImages = $this->imageFacade->getImagesByEntityIndexedById($store, null);
        $storeData->country = $store->getCountry();
        $storeData->pickupPlace = $store->isPickupPlace();
        $storeData->telephone = $store->getTelephone();
        $storeData->email = $store->getEmail();
        $storeData->region = $store->getRegion();
        $storeData->externalNumber = $store->getExternalNumber();
        $storeData->showOnStoreList = $store->isShowOnStoreList();
    }

    /**
     * @param int $selectedDomainId
     * @return \Shopsys\ShopBundle\Model\Store\StoreData
     */
    public function createForDomainId(int $selectedDomainId): StoreData
    {
        $storeData = $this->create();
        $storeData->domainId = $selectedDomainId;

        return $storeData;
    }
}
