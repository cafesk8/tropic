<?php

declare(strict_types=1);

namespace App\Model\Transport;

use Shopsys\FrameworkBundle\Model\Transport\Transport as BaseTransport;
use Shopsys\FrameworkBundle\Model\Transport\TransportData as BaseTransportData;
use Shopsys\FrameworkBundle\Model\Transport\TransportDataFactory as BaseTransportDataFactory;

class TransportDataFactory extends BaseTransportDataFactory
{
    /**
     * @return \App\Model\Transport\TransportData
     */
    public function create(): BaseTransportData
    {
        $transportData = new TransportData();
        $this->fillNew($transportData);

        return $transportData;
    }

    /**
     * @param \App\Model\Transport\Transport $transport
     * @return \App\Model\Transport\TransportData
     */
    public function createFromTransport(BaseTransport $transport): BaseTransportData
    {
        $transportData = new TransportData();
        $this->fillFromTransport($transportData, $transport);

        return $transportData;
    }

    /**
     * @param \App\Model\Transport\TransportData $transportData
     * @param \App\Model\Transport\Transport $transport
     */
    protected function fillFromTransport(BaseTransportData $transportData, BaseTransport $transport): void
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
        $transportData->trackingUrlPattern = $transport->getTrackingUrlPattern();
        $transportData->mergadoTransportType = $transport->getMergadoTransportType();
        $transportData->bulkyAllowed = $transport->isBulkyAllowed();
        $transportData->oversizedAllowed = $transport->isOversizedAllowed();

        foreach ($this->domain->getAllIds() as $domainId) {
            $transportData->actionPricesIndexedByDomainId[$domainId] = $transport->getPrice($domainId)->getActionPrice();
            $transportData->minOrderPricesIndexedByDomainId[$domainId] = $transport->getPrice($domainId)->getMinOrderPrice();
            $transportData->actionDatesFromIndexedByDomainId[$domainId] = $transport->getPrice($domainId)->getActionDateFrom();
            $transportData->actionDatesToIndexedByDomainId[$domainId] = $transport->getPrice($domainId)->getActionDateTo();
        }
    }
}
