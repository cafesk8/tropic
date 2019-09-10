<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport;

use Shopsys\FrameworkBundle\Model\Transport\Transport;
use Shopsys\FrameworkBundle\Model\Transport\TransportData;
use Shopsys\FrameworkBundle\Model\Transport\TransportDataFactory as BaseTransportDataFactory;
use Shopsys\ShopBundle\Form\Admin\TransportFormTypeExtension;

class TransportDataFactory extends BaseTransportDataFactory
{
    /**
     * @return \Shopsys\FrameworkBundle\Model\Transport\TransportData
     */
    public function create(): TransportData
    {
        $transportData = new \Shopsys\ShopBundle\Model\Transport\TransportData();
        $this->fillNew($transportData);

        return $transportData;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Transport\Transport $transport
     * @return \Shopsys\FrameworkBundle\Model\Transport\TransportData
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

        if ($transport->isChooseStore()) {
            $transportData->personalTakeType = TransportFormTypeExtension::PERSONAL_TAKE_TYPE_STORE;
        } elseif ($transport->isBalikobot()) {
            $transportData->personalTakeType = TransportFormTypeExtension::PERSONAL_TAKE_TYPE_BALIKOBOT;
        } else {
            $transportData->personalTakeType = TransportFormTypeExtension::PERSONAL_TAKE_TYPE_NONE;
        }
        $transportData->deliveryDays = $transport->getDeliveryDays();
    }
}
