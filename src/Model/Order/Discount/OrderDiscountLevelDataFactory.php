<?php

declare(strict_types=1);

namespace App\Model\Order\Discount;

class OrderDiscountLevelDataFactory
{
    /**
     * @param int $domainId
     * @return \App\Model\Order\Discount\OrderDiscountLevelData
     */
    public function createForDomainId(int $domainId): OrderDiscountLevelData
    {
        $orderDiscountLevelData = $this->create();
        $orderDiscountLevelData->domainId = $domainId;

        return $orderDiscountLevelData;
    }

    /**
     * @param \App\Model\Order\Discount\OrderDiscountLevel $orderDiscountLevel
     * @return \App\Model\Order\Discount\OrderDiscountLevelData
     */
    public function createFromOrderDiscountLevel(OrderDiscountLevel $orderDiscountLevel): OrderDiscountLevelData
    {
        $orderDiscountLevelData = $this->create();
        $orderDiscountLevelData->domainId = $orderDiscountLevel->getDomainId();
        $orderDiscountLevelData->priceLevelWithVat = $orderDiscountLevel->getPriceLevelWithVat();
        $orderDiscountLevelData->enabled = $orderDiscountLevel->isEnabled();
        $orderDiscountLevelData->discountPercent = $orderDiscountLevel->getDiscountPercent();

        return $orderDiscountLevelData;
    }

    /**
     * @return \App\Model\Order\Discount\OrderDiscountLevelData
     */
    private function create(): OrderDiscountLevelData
    {
        $orderDiscountLevelData = new OrderDiscountLevelData();
        $orderDiscountLevelData->enabled = true;

        return $orderDiscountLevelData;
    }
}
