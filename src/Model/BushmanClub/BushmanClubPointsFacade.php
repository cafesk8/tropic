<?php

declare(strict_types=1);

namespace App\Model\BushmanClub;

use App\Model\Order\OrderRepository;

class BushmanClubPointsFacade
{
    /**
     * @var float
     */
    private const POINTS_COEFICIENT = 0.05;

    /**
     * @var \App\Model\Order\OrderRepository
     */
    private $orderRepository;

    /**
     * @param \App\Model\Order\OrderRepository $orderRepository
     */
    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param int $customerId
     * @param \App\Model\BushmanClub\BushmanClubPointPeriod $bushmanClubPointPeriod
     * @return float
     */
    public function calculatePointsForCustomerAndPeriod(int $customerId, BushmanClubPointPeriod $bushmanClubPointPeriod): float
    {
        $totalOrdersProductPriceInPeriod = $this->orderRepository->getOrderProductsTotalPriceByCustomerAndDatePeriod($customerId, $bushmanClubPointPeriod->getDateFrom(), $bushmanClubPointPeriod->getDateTo());

        return $totalOrdersProductPriceInPeriod * self::POINTS_COEFICIENT;
    }
}
