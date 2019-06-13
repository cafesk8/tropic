<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order;

use Shopsys\FrameworkBundle\Model\Country\CountryFacade;
use Shopsys\FrameworkBundle\Model\Order\FrontOrderData as BaseFrontOrderData;
use Shopsys\FrameworkBundle\Model\Order\OrderDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Order\OrderDataMapper as BaseOrderDataMapper;
use Shopsys\ShopBundle\Model\Store\Store;
use Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlace;

class OrderDataMapper extends BaseOrderDataMapper
{
    /**
     * @var \Shopsys\ShopBundle\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\OrderDataFactoryInterface $orderDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Country\CountryFacade $countryFacade
     */
    public function __construct(OrderDataFactoryInterface $orderDataFactory, CountryFacade $countryFacade)
    {
        parent::__construct($orderDataFactory);
        $this->countryFacade = $countryFacade;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\FrontOrderData $frontOrderData
     * @return \Shopsys\ShopBundle\Model\Order\OrderData
     */
    public function getOrderDataFromFrontOrderData(BaseFrontOrderData $frontOrderData)
    {
        /** @var \Shopsys\ShopBundle\Model\Order\OrderData $orderData */
        $orderData = parent::getOrderDataFromFrontOrderData($frontOrderData);

        if ($orderData->transport !== null && $orderData->transport->isPickupPlace() && $frontOrderData->pickupPlace !== null) {
            $orderData->pickupPlace = $frontOrderData->pickupPlace;
            $this->setOrderDeliveryAddressDataByPickUpPlace($orderData, $frontOrderData, $orderData->pickupPlace);
        }

        if ($orderData->transport !== null && $orderData->transport->isChooseStore() && $frontOrderData->store !== null) {
            $orderData->store = $frontOrderData->store;
            $this->setOrderDeliveryAddressDataByStore($orderData, $frontOrderData, $orderData->store);
        }

        return $orderData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\OrderData $orderData
     * @param \Shopsys\ShopBundle\Model\Order\FrontOrderData $frontOrderData
     * @param \Shopsys\ShopBundle\Model\Transport\PickupPlace\PickupPlace $pickupPlace
     */
    private function setOrderDeliveryAddressDataByPickUpPlace(OrderData $orderData, FrontOrderData $frontOrderData, PickupPlace $pickupPlace): void
    {
        $orderData->deliveryFirstName = $frontOrderData->firstName;
        $orderData->deliveryLastName = $frontOrderData->lastName;

        $frontOrderData->deliveryAddressSameAsBillingAddress = false;
        $orderData->deliveryAddressSameAsBillingAddress = $frontOrderData->deliveryAddressSameAsBillingAddress;

        $frontOrderData->deliveryCompanyName = $pickupPlace->getName();
        $orderData->deliveryCompanyName = $frontOrderData->deliveryCompanyName;

        $orderData->deliveryTelephone = null;

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
     * @param \Shopsys\ShopBundle\Model\Order\OrderData $orderData
     * @param \Shopsys\ShopBundle\Model\Order\FrontOrderData $frontOrderData
     * @param \Shopsys\ShopBundle\Model\Store\Store $store
     */
    private function setOrderDeliveryAddressDataByStore(OrderData $orderData, FrontOrderData $frontOrderData, Store $store): void
    {
        $frontOrderData->deliveryFirstName = $frontOrderData->firstName;
        $orderData->deliveryFirstName = $frontOrderData->deliveryFirstName;

        $frontOrderData->deliveryLastName = $frontOrderData->lastName;
        $orderData->deliveryLastName = $frontOrderData->deliveryLastName;

        $frontOrderData->deliveryAddressSameAsBillingAddress = false;
        $orderData->deliveryAddressSameAsBillingAddress = $frontOrderData->deliveryAddressSameAsBillingAddress;

        $frontOrderData->deliveryCompanyName = $store->getName();
        $orderData->deliveryCompanyName = $frontOrderData->deliveryCompanyName;

        $orderData->deliveryTelephone = null;

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
