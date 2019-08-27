<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Cron\CronModuleFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Model\Country\Country;
use Shopsys\FrameworkBundle\Model\Payment\PaymentRepository;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Transport\Transport;
use Shopsys\FrameworkBundle\Model\Transport\TransportData;
use Shopsys\FrameworkBundle\Model\Transport\TransportFacade as BaseTransportFacade;
use Shopsys\FrameworkBundle\Model\Transport\TransportFactoryInterface;
use Shopsys\FrameworkBundle\Model\Transport\TransportPriceCalculation;
use Shopsys\FrameworkBundle\Model\Transport\TransportPriceFactoryInterface;
use Shopsys\FrameworkBundle\Model\Transport\TransportRepository;
use Shopsys\FrameworkBundle\Model\Transport\TransportVisibilityCalculation;
use Shopsys\ShopBundle\Component\Balikobot\Pickup\DownloadPickupPlacesCronModule;
use Shopsys\ShopBundle\Component\Balikobot\Pickup\PickupFacade;
use Shopsys\ShopBundle\Form\Admin\TransportFormTypeExtension;

class TransportFacade extends BaseTransportFacade
{
    /**
     * @var \Shopsys\ShopBundle\Component\Balikobot\Pickup\PickupFacade
     */
    private $pickupFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Transport\TransportRepository
     */
    protected $transportRepository;

    /**
     * @var \Shopsys\ShopBundle\Component\Cron\CronModuleFacade
     */
    private $cronModuleFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportRepository $transportRepository
     * @param \Shopsys\FrameworkBundle\Model\Payment\PaymentRepository $paymentRepository
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportVisibilityCalculation $transportVisibilityCalculation
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Component\Image\ImageFacade $imageFacade
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportPriceCalculation $transportPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportFactoryInterface $transportFactory
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportPriceFactoryInterface $transportPriceFactory
     * @param \Shopsys\ShopBundle\Component\Balikobot\Pickup\PickupFacade $pickupFacade
     * @param \Shopsys\FrameworkBundle\Component\Cron\CronModuleFacade $cronModuleFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        TransportRepository $transportRepository,
        PaymentRepository $paymentRepository,
        TransportVisibilityCalculation $transportVisibilityCalculation,
        Domain $domain,
        ImageFacade $imageFacade,
        CurrencyFacade $currencyFacade,
        TransportPriceCalculation $transportPriceCalculation,
        TransportFactoryInterface $transportFactory,
        TransportPriceFactoryInterface $transportPriceFactory,
        PickupFacade $pickupFacade,
        CronModuleFacade $cronModuleFacade
    ) {
        parent::__construct($em, $transportRepository, $paymentRepository, $transportVisibilityCalculation, $domain, $imageFacade, $currencyFacade, $transportPriceCalculation, $transportFactory, $transportPriceFactory);
        $this->pickupFacade = $pickupFacade;
        $this->cronModuleFacade = $cronModuleFacade;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\TransportData $transportData
     * @return \Shopsys\FrameworkBundle\Model\Transport\Transport
     */
    public function create(TransportData $transportData): Transport
    {
        $transportData->balikobotShipperService = $transportData->balikobotShipperService === null ? null : (string)$transportData->balikobotShipperService;
        if ($transportData->personalTakeType === TransportFormTypeExtension::PERSONAL_TAKE_TYPE_BALIKOBOT && $this->pickupFacade->isPickUpPlaceShipping($transportData->balikobotShipper, $transportData->balikobotShipperService)) {
            $transportData->pickupPlace = true;
            $transportData->initialDownload = true;
        } else {
            $transportData->pickupPlace = false;
        }

        $transport = parent::create($transportData);
        $this->scheduleCronModule();

        return $transport;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\Transport $transport
     * @param \Shopsys\ShopBundle\Model\Transport\TransportData $transportData
     */
    public function edit(Transport $transport, TransportData $transportData): void
    {
        $transportData->balikobotShipperService = $transportData->balikobotShipperService === null ? null : (string)$transportData->balikobotShipperService;
        if ($transportData->personalTakeType === TransportFormTypeExtension::PERSONAL_TAKE_TYPE_BALIKOBOT && $this->pickupFacade->isPickUpPlaceShipping($transportData->balikobotShipper, $transportData->balikobotShipperService)) {
            $transportData->pickupPlace = true;

            if ($transport->isBalikobotChanged($transportData) === true) {
                $transportData->initialDownload = true;
            }
        } else {
            $transportData->pickupPlace = false;
        }

        parent::edit($transport, $transportData);

        $this->scheduleCronModule();
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Transport\Transport[]
     */
    public function getAllPickupTransports(): array
    {
        return $this->transportRepository->getAllPickupTransports();
    }

    /**
     * @return array|\Shopsys\ShopBundle\Model\Transport\Transport[]
     */
    public function getTransportsForInitialDownload(): array
    {
        return $this->transportRepository->getTransportsForInitialDownload();
    }

    private function scheduleCronModule(): void
    {
        $this->cronModuleFacade->scheduleModuleByServiceId(DownloadPickupPlacesCronModule::class);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\Transport $transport
     */
    public function setTransportAsDownloaded(Transport $transport): void
    {
        $transport->setAsDownloaded();
        $this->em->flush($transport);
    }

    /**
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Payment\Payment[] $visiblePaymentsOnDomain
     * @param \Shopsys\FrameworkBundle\Model\Country\Country|null $country
     * @return \Shopsys\FrameworkBundle\Model\Transport\Transport[]
     */
    public function getVisibleByDomainIdAndCountry(int $domainId, array $visiblePaymentsOnDomain, ?Country $country)
    {
        /** @var \Shopsys\ShopBundle\Model\Transport\Transport[] $visibleTransports */
        $visibleTransports = $this->getVisibleByDomainId($domainId, $visiblePaymentsOnDomain);

        if ($country === null) {
            return $visibleTransports;
        }

        $transportsWithNeededCountry = [];
        foreach ($visibleTransports as $transport) {
            if ($transport->hasCountry($country)) {
                $transportsWithNeededCountry[] = $transport;
            }
        }

        return $transportsWithNeededCountry;
    }

    /**
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Payment\Payment[] $visiblePaymentsOnDomain
     * @return \Shopsys\ShopBundle\Model\Transport\Transport[]
     */
    public function getVisibleByDomainIdWithoutPickUpPlaces(int $domainId, array $visiblePaymentsOnDomain): array
    {
        $transports = $this->transportRepository->getAllByDomainId($domainId);
        $transports = $this->filterTransportsWithoutPickUpPlaces($transports);

        return $this->transportVisibilityCalculation->filterVisible($transports, $visiblePaymentsOnDomain, $domainId);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\Transport[] $transports
     * @return \Shopsys\ShopBundle\Model\Transport\Transport[]
     */
    private function filterTransportsWithoutPickUpPlaces(array $transports): array
    {
        $noPickUpPlaceTransports = [];

        foreach ($transports as $transport) {
            if (!$transport->isPickupPlaceType()) {
                $noPickUpPlaceTransports[] = $transport;
            }
        }

        return $noPickUpPlaceTransports;
    }
}
