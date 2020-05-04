<?php

declare(strict_types=1);

namespace App\Model\Transport;

use App\Component\Balikobot\Pickup\DownloadPickupPlacesCronModule;
use App\Component\Balikobot\Pickup\PickupFacade;
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

/**
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 * @property \App\Model\Payment\PaymentRepository $paymentRepository
 * @property \App\Model\Transport\TransportVisibilityCalculation $transportVisibilityCalculation
 * @property \App\Component\Image\ImageFacade $imageFacade
 * @property \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
 * @method \App\Model\Transport\Transport getById(int $id)
 * @method \App\Model\Transport\Transport[] getVisibleOnCurrentDomain(\App\Model\Payment\Payment[] $visiblePayments)
 * @method \App\Model\Transport\Transport[] getVisibleByDomainId(int $domainId, \App\Model\Payment\Payment[] $visiblePaymentsOnDomain)
 * @method \App\Model\Transport\Transport[] getAllIncludingDeleted()
 * @method \Shopsys\FrameworkBundle\Model\Pricing\Price[] getIndependentBasePricesIndexedByDomainId(\App\Model\Transport\Transport $transport)
 * @method \Shopsys\FrameworkBundle\Model\Pricing\Price[] getPricesIndexedByDomainId(\App\Model\Transport\Transport|null $transport)
 * @method updateTransportPrices(\App\Model\Transport\Transport $transport, \Shopsys\FrameworkBundle\Component\Money\Money[] $pricesIndexedByDomainId)
 * @method \App\Model\Transport\Transport getByUuid(string $uuid)
 */
class TransportFacade extends BaseTransportFacade
{
    /**
     * @var \App\Component\Balikobot\Pickup\PickupFacade
     */
    private $pickupFacade;

    /**
     * @var \App\Model\Transport\TransportRepository
     */
    protected $transportRepository;

    /**
     * @var \App\Component\Cron\CronModuleFacade
     */
    private $cronModuleFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Transport\TransportRepository $transportRepository
     * @param \App\Model\Payment\PaymentRepository $paymentRepository
     * @param \App\Model\Transport\TransportVisibilityCalculation $transportVisibilityCalculation
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportPriceCalculation $transportPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportFactoryInterface $transportFactory
     * @param \Shopsys\FrameworkBundle\Model\Transport\TransportPriceFactoryInterface $transportPriceFactory
     * @param \App\Component\Balikobot\Pickup\PickupFacade $pickupFacade
     * @param \App\Component\Cron\CronModuleFacade $cronModuleFacade
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
     * @param \App\Model\Transport\TransportData $transportData
     * @return \App\Model\Transport\Transport
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

        /** @var \App\Model\Transport\Transport $transport */
        $transport = parent::create($transportData);
        $this->scheduleCronModule();

        return $transport;
    }

    /**
     * @param \App\Model\Transport\Transport $transport
     * @param \App\Model\Transport\TransportData $transportData
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
     * @return \App\Model\Transport\Transport[]
     */
    public function getAllPickupTransports(): array
    {
        return $this->transportRepository->getAllPickupTransports();
    }

    /**
     * @return array|\App\Model\Transport\Transport[]
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
     * @param \App\Model\Transport\Transport $transport
     */
    public function setTransportAsDownloaded(Transport $transport): void
    {
        $transport->setAsDownloaded();
        $this->em->flush($transport);
    }

    /**
     * @param int $domainId
     * @param \App\Model\Payment\Payment[] $visiblePaymentsOnDomain
     * @param \App\Model\Country\Country|null $country
     * @param bool $showEmailTransportInCart
     * @return \App\Model\Transport\Transport[]
     */
    public function getVisibleByDomainIdAndCountryAndTransportEmailType(int $domainId, array $visiblePaymentsOnDomain, ?Country $country, bool $showEmailTransportInCart)
    {
        /** @var \App\Model\Transport\Transport[] $visibleTransports */
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
     * @param \App\Model\Payment\Payment[] $visiblePaymentsOnDomain
     * @param bool $showEmailTransportInCart
     * @return \App\Model\Transport\Transport[]
     */
    private function getVisibleByDomainIdAndTransportEmailType(int $domainId, array $visiblePaymentsOnDomain, bool $showEmailTransportInCart)
    {
        $transports = $this->transportRepository->getAllByDomainIdAndTransportEmailType($domainId, $showEmailTransportInCart);

        return $this->transportVisibilityCalculation->filterVisible($transports, $visiblePaymentsOnDomain, $domainId);
    }

    /**
     * @param string $mallId
     * @return \App\Model\Transport\Transport|null
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
     * @param \App\Model\Pricing\Currency\Currency $currency
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Component\Money\Money[]
     */
    public function getTransportPricesWithVatByCurrencyAndDomainIdIndexedByTransportId(Currency $currency, int $domainId): array
    {
        $transportPricesWithVatByTransportId = [];
        $transports = $this->getAllIncludingDeleted();
        foreach ($transports as $transport) {
            try {
                $transportPrice = $this->transportPriceCalculation->calculateIndependentPrice($transport, $currency, $domainId);
                $transportPricesWithVatByTransportId[$transport->getId()] = $transportPrice->getPriceWithVat();
            } catch (TransportPriceNotFoundException $exception) {
                $transportPricesWithVatByTransportId[$transport->getId()] = Money::zero();
            }
        }

        return $transportPricesWithVatByTransportId;
    }
}
