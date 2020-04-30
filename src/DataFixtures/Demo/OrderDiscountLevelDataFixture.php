<?php

declare(strict_types=1);

namespace App\DataFixtures\Demo;

use App\Component\Domain\DomainHelper;
use App\Model\Order\Discount\OrderDiscountLevelDataFactory;
use App\Model\Order\Discount\OrderDiscountLevelFacade;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Shopsys\FrameworkBundle\Component\Money\Money;

class OrderDiscountLevelDataFixture implements FixtureInterface
{
    /**
     * @var \App\Model\Order\Discount\OrderDiscountLevelFacade
     */
    private $orderDiscountLevelFacade;

    /**
     * @var \App\Model\Order\Discount\OrderDiscountLevelDataFactory
     */
    private $orderDiscountLevelDataFactory;

    /**
     * @param \App\Model\Order\Discount\OrderDiscountLevelFacade $orderDiscountLevelFacade
     * @param \App\Model\Order\Discount\OrderDiscountLevelDataFactory $orderDiscountLevelDataFactory
     */
    public function __construct(OrderDiscountLevelFacade $orderDiscountLevelFacade, OrderDiscountLevelDataFactory $orderDiscountLevelDataFactory)
    {
        $this->orderDiscountLevelFacade = $orderDiscountLevelFacade;
        $this->orderDiscountLevelDataFactory = $orderDiscountLevelDataFactory;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->createOrderDiscountLevel(DomainHelper::CZECH_DOMAIN, 5, '2000', true);
        $this->createOrderDiscountLevel(DomainHelper::CZECH_DOMAIN, 10, '5000', true);
        $this->createOrderDiscountLevel(DomainHelper::CZECH_DOMAIN, 50, '10000', false);
        $this->createOrderDiscountLevel(DomainHelper::SLOVAK_DOMAIN, 5, '70', true);
        $this->createOrderDiscountLevel(DomainHelper::ENGLISH_DOMAIN, 5, '70', true);
    }

    /**
     * @param int $domainId
     * @param int $discountPercent
     * @param string $priceLevelWithVat
     * @param bool $enabled
     */
    private function createOrderDiscountLevel(int $domainId, int $discountPercent, string $priceLevelWithVat, bool $enabled): void
    {
        $orderDiscountLevelData = $this->orderDiscountLevelDataFactory->createForDomainId($domainId);
        $orderDiscountLevelData->discountPercent = $discountPercent;
        $orderDiscountLevelData->priceLevelWithVat = Money::create($priceLevelWithVat);
        $orderDiscountLevelData->enabled = $enabled;
        $this->orderDiscountLevelFacade->create($orderDiscountLevelData);
    }
}
