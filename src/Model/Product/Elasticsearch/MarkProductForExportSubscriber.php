<?php

declare(strict_types=1);

namespace App\Model\Product\Elasticsearch;

use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityEvent;
use Shopsys\FrameworkBundle\Model\Product\Brand\BrandEvent;
use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\MarkProductForExportSubscriber as BaseMarkProductForExportSubscriber;
use Shopsys\FrameworkBundle\Model\Product\Flag\FlagEvent;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterEvent;
use Shopsys\FrameworkBundle\Model\Product\ProductFacade;
use Shopsys\FrameworkBundle\Model\Product\Unit\UnitEvent;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @property \App\Model\Product\ProductFacade $productFacade
 */
class MarkProductForExportSubscriber extends BaseMarkProductForExportSubscriber
{
    private Logger $logger;

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     * @param \App\Model\Product\ProductFacade $productFacade
     */
    public function __construct(Logger $logger, ProductFacade $productFacade)
    {
        $this->logger = $logger;
        parent::__construct($productFacade);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityEvent $availabilityEvent
     */
    public function markAffectedByAvailability(AvailabilityEvent $availabilityEvent): void
    {
        $this->logger->addInfo('MarkProductForExportSubscriber call', [
            'method' => 'markAffectedByAvailability',
        ]);
        parent::markAffectedByAvailability($availabilityEvent);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Brand\BrandEvent $brandEvent
     */
    public function markAffectedByBrand(BrandEvent $brandEvent): void
    {
        $this->logger->addInfo('MarkProductForExportSubscriber call', [
            'method' => 'markAffectedByBrand',
        ]);
        parent::markAffectedByBrand($brandEvent);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Flag\FlagEvent $flagEvent
     */
    public function markAffectedByFlag(FlagEvent $flagEvent): void
    {
        $this->logger->addInfo('MarkProductForExportSubscriber call', [
            'method' => 'markAffectedByFlag',
        ]);
        parent::markAffectedByFlag($flagEvent);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterEvent $parameterEvent
     */
    public function markAffectedByParameter(ParameterEvent $parameterEvent): void
    {
        $this->logger->addInfo('MarkProductForExportSubscriber call', [
            'method' => 'markAffectedByParameter',
        ]);
        parent::markAffectedByParameter($parameterEvent);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Unit\UnitEvent $unitEvent
     */
    public function markAffectedByUnit(UnitEvent $unitEvent): void
    {
        $this->logger->addInfo('MarkProductForExportSubscriber call', [
            'method' => 'markAffectedByUnit',
        ]);
        parent::markAffectedByUnit($unitEvent);
    }

    /**
     * @param \Symfony\Contracts\EventDispatcher\Event $event
     */
    public function markAll(Event $event): void
    {
        $this->logger->addInfo('MarkProductForExportSubscriber call', [
            'method' => 'markAll',
        ]);
        parent::markAll($event);
    }
}
