<?php

declare(strict_types=1);

namespace App\Component\Balikobot\Pickup;

use App\Component\String\StringHelper;
use App\Model\Country\CountryFacade;
use App\Model\Transport\PickupPlace\PickupPlaceData;
use App\Model\Transport\PickupPlace\PickupPlaceFacade;
use App\Model\Transport\Transport;
use App\Model\Transport\TransportFacade;
use Shopsys\Plugin\Cron\SimpleCronModuleInterface;
use Symfony\Bridge\Monolog\Logger;

class DownloadPickupPlacesCronModule implements SimpleCronModuleInterface
{
    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @var \App\Model\Transport\TransportFacade
     */
    private $transportFacade;

    /**
     * @var \App\Component\Balikobot\Pickup\PickupFacade
     */
    private $pickupFacade;

    /**
     * @var \App\Model\Transport\PickupPlace\PickupPlaceFacade
     */
    private $pickupPlaceFacade;

    /**
     * @var \App\Model\Country\CountryFacade
     */
    private $countryFacade;

    /**
     * @param \App\Model\Transport\TransportFacade $transportFacade
     * @param \App\Component\Balikobot\Pickup\PickupFacade $pickupFacade
     * @param \App\Model\Transport\PickupPlace\PickupPlaceFacade $pickupPlaceFacade
     * @param \App\Model\Country\CountryFacade $countryFacade
     */
    public function __construct(TransportFacade $transportFacade, PickupFacade $pickupFacade, PickupPlaceFacade $pickupPlaceFacade, CountryFacade $countryFacade)
    {
        $this->transportFacade = $transportFacade;
        $this->pickupFacade = $pickupFacade;
        $this->pickupPlaceFacade = $pickupPlaceFacade;
        $this->countryFacade = $countryFacade;
    }

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * This method is called to run the CRON module.
     */
    public function run(): void
    {
        $transports = $this->transportFacade->getTransportsForInitialDownload();
        $availableCountries = $this->countryFacade->getAllCodesInArray();

        if (count($transports) <= 0) {
            $transports = $this->transportFacade->getAllPickupTransports();
        }

        foreach ($transports as $transport) {
            $pickupPlaceData = [];

            $pickupPlacesResponseData = $this->pickupFacade->getPickupPlaces($transport->getBalikobotShipper(), $transport->getBalikobotShipperService());

            foreach ($pickupPlacesResponseData as $pickupPlaceResponseData) {
                $preparedPickupPlaceData = $this->preparePickupPlaceData($pickupPlaceResponseData, $transport, $availableCountries);

                if ($preparedPickupPlaceData !== null) {
                    $pickupPlaceData[] = $preparedPickupPlaceData;
                    $this->logger->addInfo(
                        sprintf(
                            'PickupPlace with balikobot ID `%s` is prepared for update for shipper `%s` and service `%s`',
                            $preparedPickupPlaceData->balikobotId,
                            $preparedPickupPlaceData->balikobotShipper,
                            $preparedPickupPlaceData->balikobotShipperService
                        )
                    );
                }
            }

            $this->pickupPlaceFacade->createOrEditForArray($transport->getBalikobotShipper(), $transport->getBalikobotShipperService(), $pickupPlaceData);
            $this->transportFacade->setTransportAsDownloaded($transport);
        }
    }

    /**
     * @param array $pickupPlaceResponseData
     * @param \App\Model\Transport\Transport $transport
     * @param string[] $availableCountries
     * @return \App\Model\Transport\PickupPlace\PickupPlaceData|null
     */
    private function preparePickupPlaceData(array $pickupPlaceResponseData, Transport $transport, array $availableCountries): ?PickupPlaceData
    {
        if ($pickupPlaceResponseData['type'] !== 'branch') {
            return null;
        }

        if (in_array($pickupPlaceResponseData['country'], $availableCountries, true) === false) {
            return null;
        }

        $pickupPlaceData = new PickupPlaceData();
        $pickupPlaceData->balikobotId = $pickupPlaceResponseData['id'];
        $pickupPlaceData->balikobotShipper = $transport->getBalikobotShipper();
        $pickupPlaceData->balikobotShipperService = $transport->getBalikobotShipperService();

        $pickupPlaceData->name = $pickupPlaceResponseData['name'];
        $pickupPlaceData->city = $pickupPlaceResponseData['city'];
        $pickupPlaceData->street = $pickupPlaceResponseData['street'];
        $pickupPlaceData->postCode = StringHelper::removeWhitespaces($pickupPlaceResponseData['zip']);
        $pickupPlaceData->countryCode = $pickupPlaceResponseData['country'];

        return $pickupPlaceData;
    }
}
