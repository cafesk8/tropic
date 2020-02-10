<?php

declare(strict_types=1);

namespace App\Model\Order;

use App\Model\Store\Store;
use App\Model\Transport\PickupPlace\PickupPlace;
use Shopsys\FrameworkBundle\Model\Country\CountryFacade;
use Shopsys\FrameworkBundle\Model\Order\FrontOrderData as BaseFrontOrderData;
use Shopsys\FrameworkBundle\Model\Order\OrderDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\OrderDataMapper as BaseOrderDataMapper;

/**
 * @property \App\Model\Order\OrderDataFactory $orderDataFactory
 */
class OrderDataMapper extends BaseOrderDataMapper
{
    /**
     * @var \App\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @param \App\Model\Order\OrderDataFactory $orderDataFactory
     * @param \App\Model\Country\CountryFacade $countryFacade
     */
    public function __construct(OrderDataFactoryInterface $orderDataFactory, CountryFacade $countryFacade)
    {
        parent::__construct($orderDataFactory);
        $this->countryFacade = $countryFacade;
    }

    /**
     * @param \App\Model\Order\FrontOrderData $frontOrderData
     * @return \App\Model\Order\OrderData
     */
    public function getOrderDataFromFrontOrderData(BaseFrontOrderData $frontOrderData)
    {
        /** @var \App\Model\Order\OrderData $orderData */
        $orderData = parent::getOrderDataFromFrontOrderData($frontOrderData);

        $orderData->deliveryAddressSameAsBillingAddress = false;

        if ($orderData->transport !== null && $orderData->transport->isPickupPlace() && $frontOrderData->pickupPlace !== null) {
            $orderData->pickupPlace = $frontOrderData->pickupPlace;
            $this->setOrderDeliveryAddressDataByPickUpPlace($orderData, $frontOrderData, $orderData->pickupPlace);
        }

        if ($orderData->transport !== null && $orderData->transport->isChooseStore() && $frontOrderData->store !== null) {
            $orderData->store = $frontOrderData->store;
            $this->setOrderDeliveryAddressDataByStore($orderData, $frontOrderData, $orderData->store);
        }

        $orderData->deliveryCountry = $frontOrderData->country;

        return $orderData;
    }

    /**
     * @param \App\Model\Order\OrderData $orderData
     * @param \App\Model\Order\FrontOrderData $frontOrderData
     * @param \App\Model\Transport\PickupPlace\PickupPlace $pickupPlace
     */
    private function setOrderDeliveryAddressDataByPickUpPlace(OrderData $orderData, FrontOrderData $frontOrderData, PickupPlace $pickupPlace): void
    {
        $orderData->deliveryFirstName = $frontOrderData->deliveryFirstName;
        $orderData->deliveryLastName = $frontOrderData->deliveryLastName;

        $frontOrderData->deliveryCompanyName = $pickupPlace->getName();
        $orderData->deliveryCompanyName = $frontOrderData->deliveryCompanyName;

        $orderData->deliveryTelephone = $frontOrderData->deliveryTelephone;

        $frontOrderData->deliveryStreet = $pickupPlace->getStreet();
        $orderData->deliveryStreet = $frontOrderData->deliveryStreet;

        $frontOrderData->deliveryCity = $pickupPlace->getCity();
        $orderData->deliveryCity = $frontOrderData->deliveryCity;

        $frontOrderData->deliveryPostcode = $pickupPlace->getPostCode();
        $orderData->deliveryPostcode = $frontOrderData->deliveryPostcode;

        $frontOrderData->deliveryCountry = $this->countryFacade->getByCode($pickupPlace->getCountryCode());
        $orderData->deliveryCountry = $frontOrderData->deliveryCountry;
    }

    /**
     * @param \App\Model\Order\OrderData $orderData
     * @param \App\Model\Order\FrontOrderData $frontOrderData
     * @param \App\Model\Store\Store $store
     */
    private function setOrderDeliveryAddressDataByStore(OrderData $orderData, FrontOrderData $frontOrderData, Store $store): void
    {
        $orderData->deliveryFirstName = $frontOrderData->deliveryFirstName;

        $orderData->deliveryLastName = $frontOrderData->deliveryLastName;

        $frontOrderData->deliveryCompanyName = $store->getName();
        $orderData->deliveryCompanyName = $frontOrderData->deliveryCompanyName;

        $orderData->deliveryTelephone = $frontOrderData->deliveryTelephone;

        $frontOrderData->deliveryStreet = $store->getStreet();
        $orderData->deliveryStreet = $frontOrderData->deliveryStreet;

        $frontOrderData->deliveryCity = $store->getCity();
        $orderData->deliveryCity = $frontOrderData->deliveryCity;

        $frontOrderData->deliveryPostcode = $store->getPostcode();
        $orderData->deliveryPostcode = $frontOrderData->deliveryPostcode;

        $frontOrderData->deliveryCountry = $store->getCountry();
        $orderData->deliveryCountry = $frontOrderData->deliveryCountry;
    }
}
