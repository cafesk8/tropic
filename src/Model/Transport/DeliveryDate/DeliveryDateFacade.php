<?php

declare(strict_types=1);

namespace App\Model\Transport\DeliveryDate;

use App\Component\Setting\Setting;
use App\Model\Product\Product;
use App\Model\Transport\Transport;
use DateInterval;
use DateTime;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Payment\PaymentFacade;
use Shopsys\FrameworkBundle\Model\Transport\TransportFacade;

class DeliveryDateFacade
{
    /**
     * @var \App\Model\Transport\TransportFacade
     */
    private $transportFacade;

    /**
     * @var \App\Model\Payment\PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var \App\Model\Transport\DeliveryDate\WorkdayService
     */
    private $workdayService;

    /**
     * @var \App\Component\Setting\Setting
     */
    private $setting;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var int|null
     */
    private $fastestTransportDeliveryDays;

    /**
     * @param \App\Model\Transport\TransportFacade $transportFacade
     * @param \App\Model\Payment\PaymentFacade $paymentFacade
     * @param \App\Model\Transport\DeliveryDate\WorkdayService $workdayService
     * @param \App\Component\Setting\Setting $setting
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        TransportFacade $transportFacade,
        PaymentFacade $paymentFacade,
        WorkdayService $workdayService,
        Setting $setting,
        Domain $domain
    ) {
        $this->transportFacade = $transportFacade;
        $this->paymentFacade = $paymentFacade;
        $this->workdayService = $workdayService;
        $this->setting = $setting;
        $this->domain = $domain;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Transport\Transport|null $transport
     * @return \DateTime
     */
    public function getExpectedDeliveryDate(Product $product, ?Transport $transport = null): DateTime
    {
        if ($transport === null) {
            if ($this->fastestTransportDeliveryDays === null) {
                $this->fastestTransportDeliveryDays = $this->getFastestTransportDeliveryDays();
            }
            return $this->calculateDeliveryDate($this->fastestTransportDeliveryDays, $product);
        }

        return $this->calculateDeliveryDate(
            $transport->getDeliveryDays(),
            $product
        );
    }

    /**
     * @param int $deliveryDays
     * @param \App\Model\Product\Product $product
     * @return \DateTime
     */
    private function calculateDeliveryDate(int $deliveryDays, Product $product): DateTime
    {
        $now = new DateTime();
        $startingDateTime = new DateTime();

        $orderDeadline = $this->getOrderDeadline();
        if ($now > $orderDeadline) {
            $startingDateTime = $startingDateTime->add(DateInterval::createFromDateString('1 day'));
        }

        $this->addTwoDaysForNonStoredProductInCentralStore($product, $startingDateTime);

        return $this->workdayService->getFirstWorkdayAfterGivenWorkdaysCount(
            $startingDateTime,
            $deliveryDays,
            $this->domain->getLocale()
        );
    }

    /**
     * @return \DateTime
     */
    public function getOrderDeadline(): DateTime
    {
        $orderDeadline = new DateTime();
        $orderDeadline->setTime(
            $this->setting->getForDomain(Setting::ORDER_TRANSPORT_DEADLINE_HOURS, $this->domain->getId()),
            $this->setting->getForDomain(Setting::ORDER_TRANSPORT_DEADLINE_MINUTES, $this->domain->getId())
        );

        return $orderDeadline;
    }

    /**
     * @return int
     */
    private function getFastestTransportDeliveryDays(): int
    {
        $allTransportsDeliveryDays = [];
        foreach ($this->getVisibleTransports() as $transport) {
            $allTransportsDeliveryDays[] = $transport->getDeliveryDays();
        }

        if (count($allTransportsDeliveryDays) === 0) {
            throw new \App\Model\Transport\DeliveryDate\Exception\NoVisibleTransportsWithoutPickUpPlacesOnDomainException();
        }

        return min($allTransportsDeliveryDays);
    }

    /**
     * @return \App\Model\Transport\Transport[]
     */
    private function getVisibleTransports(): array
    {
        $visiblePayments = $this->paymentFacade->getVisibleByDomainId(
            $this->domain->getId()
        );
        $transports = $this->transportFacade->getVisibleByDomainIdWithoutPickUpPlaces(
            $this->domain->getId(),
            $visiblePayments
        );

        return $transports;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \DateTime $dateTime
     */
    private function addTwoDaysForNonStoredProductInCentralStore(Product $product, DateTime $dateTime): void
    {
        $productStoreStocks = $product->getStocksWithoutZeroQuantityOnCentralStore();
        if (count($productStoreStocks) === 0) {
            $dateTime->add(new \DateInterval('P2D')); // add 2 days for expected delivery date
        }
    }
}
