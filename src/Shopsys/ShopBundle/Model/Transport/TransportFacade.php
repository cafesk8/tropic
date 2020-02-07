<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Cron\CronModuleFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Country\Country;
use Shopsys\FrameworkBundle\Model\Payment\PaymentRepository;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Transport\Exception\TransportPriceNotFoundException;
use Shopsys\FrameworkBundle\Model\Transport\Transport as BaseTransport;
use Shopsys\FrameworkBundle\Model\Transport\TransportData;
use Shopsys\FrameworkBundle\Model\Transport\TransportFacade as BaseTransportFacade;
use Shopsys\FrameworkBundle\Model\Transport\TransportFactoryInterface;
use Shopsys\FrameworkBundle\Model\Transport\TransportPriceCalculation;
use Shopsys\FrameworkBundle\Model\Transport\TransportPriceFactoryInterface;
use Shopsys\FrameworkBundle\Model\Transport\TransportRepository;
use Shopsys\FrameworkBundle\Model\Transport\TransportVisibilityCalculation;
use Shopsys\ShopBundle\Component\Balikobot\Pickup\DownloadPickupPlacesCronModule;
use Shopsys\ShopBundle\Component\Balikobot\Pickup\PickupFacade;

/**
 * @property \Shopsys\ShopBundle\Model\Payment\PaymentRepository $paymentRepository
 * @property \Shopsys\ShopBundle\Model\Transport\TransportVisibilityCalculation $transportVisibilityCalculation
 * @property \Shopsys\ShopBundle\Component\Image\ImageFacade $imageFacade
 * @property \Shopsys\ShopBundle\Model\Pricing\Currency\CurrencyFacade $currencyFacade
 * @method \Shopsys\ShopBundle\Model\Transport\Transport getById(int $id)
 * @method \Shopsys\ShopBundle\Model\Transport\Transport[] getVisibleOnCurrentDomain(\Shopsys\ShopBundle\Model\Payment\Payment[] $visiblePayments)
 * @method \Shopsys\ShopBundle\Model\Transport\Transport[] getVisibleByDomainId(int $domainId, \Shopsys\ShopBundle\Model\Payment\Payment[] $visiblePaymentsOnDomain)
 * @method updateTransportPrices(\Shopsys\ShopBundle\Model\Transport\Transport $transport, \Shopsys\FrameworkBundle\Component\Money\Money[] $pricesByCurrencyId)
 * @method \Shopsys\ShopBundle\Model\Transport\Transport[] getAllIncludingDeleted()
 * @method \Shopsys\FrameworkBundle\Model\Pricing\Price[] getIndependentBasePricesIndexedByCurrencyId(\Shopsys\ShopBundle\Model\Transport\Transport $transport)
 */
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
     * @param \Shopsys\ShopBundle\Model\Transport\TransportRepository $transportRepository
     * @param \Shopsys\ShopBundle\Model\Payment\PaymentRepository $paymentRepository
     * @param \Shopsys\ShopBundle\Model\Transport\TransportVisibilityCalculation $transportVisibilityCalculation
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\ShopBundle\Component\Image\ImageFacade $imageFacade
     * @param \Shopsys\ShopBundle\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportPriceCalculation $transportPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportFactoryInterface $transportFactory
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportPriceFactoryInterface $transportPriceFactory
     * @param \Shopsys\ShopBundle\Component\Balikobot\Pickup\PickupFacade $pickupFacade
     * @param \Shopsys\ShopBundle\Component\Cron\CronModuleFacade $cronModuleFacade
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
     * @return \Shopsys\ShopBundle\Model\Transport\Transport
     */
    public function create(TransportData $transportData): Transport
    {
        $transportData->balikobotShipperService = $transportData->balikobotShipperService === null ? null : (string)$transportData->balikobotShipperService;
        if ($transportData->transportType === Transport::TYPE_PERSONAL_TAKE_BALIKOBOT && $this->pickupFacade->isPickUpPlaceShipping($transportData->balikobotShipper, $transportData->balikobotShipperService)) {
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
    public function edit(BaseTransport $transport, TransportData $transportData): void
    {
        $transportData->balikobotShipperService = $transportData->balikobotShipperService === null ? null : (string)$transportData->balikobotShipperService;
        if ($transportData->transportType === Transport::TYPE_PERSONAL_TAKE_BALIKOBOT && $this->pickupFacade->isPickUpPlaceShipping($transportData->balikobotShipper, $transportData->balikobotShipperService)) {
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
     * @param \Shopsys\ShopBundle\Model\Payment\Payment[] $visiblePaymentsOnDomain
     * @param \Shopsys\ShopBundle\Model\Country\Country|null $country
     * @param bool $showEmailTransportInCart
     * @return \Shopsys\ShopBundle\Model\Transport\Transport[]
     */
    public function getVisibleByDomainIdAndCountryAndTransportEmailType(int $domainId, array $visiblePaymentsOnDomain, ?Country $country, bool $showEmailTransportInCart)
    {
        /** @var \Shopsys\ShopBundle\Model\Transport\Transport[] $visibleTransports */
        $visibleTransports = $this->getVisibleByDomainIdAndTransportEmailType($domainId, $visiblePaymentsOnDomain, $showEmailTransportInCart);

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
     * @param \Shopsys\ShopBundle\Model\Payment\Payment[] $visiblePaymentsOnDomain
     * @return \Shopsys\ShopBundle\Model\Transport\Transport[]
     */
    public function getVisibleByDomainIdWithoutPickUpPlaces(int $domainId, array $visiblePaymentsOnDomain): array
    {
        $transports = $this->transportRepository->getAllByDomainId($domainId);
        $transports = $this->filterTransportsWithoutPickUpPlaces($transports);

        return $this->transportVisibilityCalculation->filterVisible($transports, $visiblePaymentsOnDomain, $domainId);
    }

    /**
     * @param int $domainId
     * @param \Shopsys\ShopBundle\Model\Payment\Payment[] $visiblePaymentsOnDomain
     * @param bool $showEmailTransportInCart
     * @return \Shopsys\ShopBundle\Model\Transport\Transport[]
     */
    private function getVisibleByDomainIdAndTransportEmailType(int $domainId, array $visiblePaymentsOnDomain, bool $showEmailTransportInCart)
    {
        $transports = $this->transportRepository->getAllByDomainIdAndTransportEmailType($domainId, $showEmailTransportInCart);

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

    /**
     * @param string $mallId
     * @return \Shopsys\ShopBundle\Model\Transport\Transport|null
     */
    public function getFirstTransportByMallTransportName(string $mallId): ?Transport
    {
        $transportsByMallId = $this->transportRepository->getByMallTransportName($mallId);

        if (count($transportsByMallId) > 0) {
            return $transportsByMallId[0];
        }

        return null;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency $currency
     * @return \Shopsys\FrameworkBundle\Component\Money\Money[]
     */
    public function getTransportPricesWithVatIndexedByTransportId(Currency $currency): array
    {
        $transportPricesWithVatByTransportId = [];
        $transports = $this->getAllIncludingDeleted();
        foreach ($transports as $transport) {
            try {
                $transportPrice = $this->transportPriceCalculation->calculateIndependentPrice($transport, $currency);
                $transportPricesWithVatByTransportId[$transport->getId()] = $transportPrice->getPriceWithVat();
            } catch (TransportPriceNotFoundException $exception) {
                $transportPricesWithVatByTransportId[$transport->getId()] = Money::zero();
            }
        }

        return $transportPricesWithVatByTransportId;
    }
}
