<?php

declare(strict_types=1);

namespace App\Model\Order\Gift;

class OrderGiftDataFactory
{
    /**
     * @var \App\Model\Order\Gift\OrderGiftFacade
     */
    protected $orderGiftFacade;

    /**
     * @param \App\Model\Order\Gift\OrderGiftFacade $orderGiftFacade
     */
    public function __construct(OrderGiftFacade $orderGiftFacade)
    {
        $this->orderGiftFacade = $orderGiftFacade;
    }

    /**
     * @param int $domainId
     * @return \App\Model\Order\Gift\OrderGiftData
     */
    public function createForDomainId(int $domainId): OrderGiftData
    {
        $orderGiftData = $this->createEmpty();
        $orderGiftData->domainId = $domainId;

        return $orderGiftData;
    }

    /**
     * @param \App\Model\Order\Gift\OrderGift $orderGift
     * @return \App\Model\Order\Gift\OrderGiftData
     */
    public function createFromOrderGift(OrderGift $orderGift): OrderGiftData
    {
        $orderGiftData = $this->createEmpty();
        $orderGiftData->enabled = $orderGift->isEnabled();
        $orderGiftData->products = $orderGift->getProducts();
        $orderGiftData->domainId = $orderGift->getDomainId();
        $orderGiftData->priceLevelWithVat = $orderGift->getPriceLevelWithVat();

        return $orderGiftData;
    }

    /**
     * @return \App\Model\Order\Gift\OrderGiftData
     */
    private function createEmpty(): OrderGiftData
    {
        $orderGiftData = new OrderGiftData();
        $orderGiftData->products = [];
        $orderGiftData->enabled = true;

        return $orderGiftData;
    }
}
