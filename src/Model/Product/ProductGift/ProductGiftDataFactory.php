<?php

declare(strict_types=1);

namespace App\Model\Product\ProductGift;

class ProductGiftDataFactory
{
    /**
     * @return \App\Model\Product\ProductGift\ProductGiftData
     */
    public function create(): ProductGiftData
    {
        $productGiftData = new ProductGiftData();

        $this->fillNew($productGiftData);

        return $productGiftData;
    }

    /**
     * @param int $domainId
     * @return \App\Model\Product\ProductGift\ProductGiftData
     */
    public function createForDomainId(int $domainId): ProductGiftData
    {
        $productGiftData = $this->create();

        $productGiftData->domainId = $domainId;

        return $productGiftData;
    }

    /**
     * @param \App\Model\Product\ProductGift\ProductGift $productGift
     * @return \App\Model\Product\ProductGift\ProductGiftData
     */
    public function createFromProductGift(ProductGift $productGift): ProductGiftData
    {
        $productGiftData = $this->create();
        $this->fillFromProductGift($productGiftData, $productGift);

        return $productGiftData;
    }

    /**
     * @param \App\Model\Product\ProductGift\ProductGiftData $productGiftData
     * @param \App\Model\Product\ProductGift\ProductGift $productGift
     */
    private function fillFromProductGift(ProductGiftData $productGiftData, ProductGift $productGift): void
    {
        $productGiftData->gift = $productGift->getGift();
        $productGiftData->products = $productGift->getProducts();
        $productGiftData->domainId = $productGift->getDomainId();
        $productGiftData->active = $productGift->isActive();
        $productGiftData->title = $productGift->getTitle();
    }

    /**
     * @param \App\Model\Product\ProductGift\ProductGiftData $productGiftData
     */
    private function fillNew(ProductGiftData $productGiftData): void
    {
        $productGiftData->active = false;
    }
}
