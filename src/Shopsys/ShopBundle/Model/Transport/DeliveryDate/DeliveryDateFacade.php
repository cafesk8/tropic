<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport\DeliveryDate;

use DateInterval;
use DateTime;
use DateTimeZone;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Payment\PaymentFacade;
use Shopsys\FrameworkBundle\Model\Transport\TransportFacade;
use Shopsys\ShopBundle\Component\Setting\Setting;
use Shopsys\ShopBundle\Model\Transport\Transport;

class DeliveryDateFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Transport\TransportFacade
     */
    private $transportFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Payment\PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Transport\DeliveryDate\WorkdayService
     */
    private $workdayService;

    /**
     * @var \Shopsys\ShopBundle\Component\Setting\Setting
     */
    private $setting;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\ShopBundle\Model\Transport\TransportFacade $transportFacade
     * @param \Shopsys\FrameworkBundle\Model\Payment\PaymentFacade $paymentFacade
     * @param \Shopsys\ShopBundle\Model\Transport\DeliveryDate\WorkdayService $workdayService
     * @param \Shopsys\ShopBundle\Component\Setting\Setting $setting
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
     * @param \Shopsys\ShopBundle\Model\Transport\Transport|null $transport
     * @return \DateTime
     */
    public function getExpectedDeliveryDate(?Transport $transport = null): DateTime
    {
        if ($transport === null) {
            return $this->calculateDeliveryDate(
                $this->getFastestTransportDeliveryDays()
            );
        }

        return $this->calculateDeliveryDate(
            $transport->getDeliveryDays()
        );
    }

    /**
     * @param int $deliveryDays
     * @return \DateTime
     */
    private function calculateDeliveryDate(int $deliveryDays): DateTime
    {
        $now = new DateTime();
        $startingDateTime = new DateTime();

        $orderDeadline = $this->getOrderDeadline();
        if ($now > $orderDeadline) {
            $startingDateTime = $startingDateTime->add(DateInterval::createFromDateString('1 day'));
        }

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
        $orderDeadline->setTimezone(
            new DateTimeZone('Europe/Prague')
        );
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
            throw new \Shopsys\ShopBundle\Model\Transport\DeliveryDate\Exception\NoVisibleTransportsWithoutPickUpPlacesOnDomainException();
        }

        return min($allTransportsDeliveryDays);
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Transport\Transport[]
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
}
