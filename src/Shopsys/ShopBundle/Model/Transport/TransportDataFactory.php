<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport;

use Shopsys\FrameworkBundle\Model\Transport\Transport;
use Shopsys\FrameworkBundle\Model\Transport\TransportData;
use Shopsys\FrameworkBundle\Model\Transport\TransportDataFactory as BaseTransportDataFactory;

class TransportDataFactory extends BaseTransportDataFactory
{
    /**
     * @return \Shopsys\ShopBundle\Model\Transport\TransportData
     */
    public function create(): TransportData
    {
        $transportData = new \Shopsys\ShopBundle\Model\Transport\TransportData();
        $this->fillNew($transportData);

        return $transportData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\Transport $transport
     * @return \Shopsys\ShopBundle\Model\Transport\TransportData
     */
    public function createFromTransport(Transport $transport): TransportData
    {
        $transportData = new \Shopsys\ShopBundle\Model\Transport\TransportData();
        $this->fillFromTransport($transportData, $transport);

        return $transportData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\TransportData $transportData
     * @param \Shopsys\ShopBundle\Model\Transport\Transport $transport
     */
    protected function fillFromTransport(TransportData $transportData, Transport $transport): void
    {
        parent::fillFromTransport($transportData, $transport);
        $transportData->balikobotShipper = $transport->getBalikobotShipper();
        $transportData->balikobotShipperService = $transport->getBalikobotShipperService();
        $transportData->pickupPlace = $transport->isPickupPlace();
        $transportData->initialDownload = $transport->isInitialDownload();
        $transportData->countries = $transport->getCountries();
        $transportData->mallType = $transport->getMallType();
        $transportData->externalId = $transport->getExternalId();
        $transportData->transportType = $transport->getTransportType();
        $transportData->deliveryDays = $transport->getDeliveryDays();
        $transportData->trackingUrlPattern = $transport->getTrackingUrlPattern();
    }
}
