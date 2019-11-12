<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\ProductGift;

class ProductGiftDataFactory
{
    /**
     * @return \Shopsys\ShopBundle\Model\Product\ProductGift\ProductGiftData
     */
    public function create(): ProductGiftData
    {
        $productGiftData = new ProductGiftData();

        $this->fillNew($productGiftData);

        return $productGiftData;
    }

    /**
     * @param $domainId
     * @return \Shopsys\ShopBundle\Model\Product\ProductGift\ProductGiftData
     */
    public function createForDomainId(int $domainId): ProductGiftData
    {
        $productGiftData = $this->create();

        $productGiftData->domainId = $domainId;

        return $productGiftData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductGift\ProductGift $productGift
     * @return \Shopsys\ShopBundle\Model\Product\ProductGift\ProductGiftData
     */
    public function createFromProductGift(ProductGift $productGift): ProductGiftData
    {
        $productGiftData = $this->create();
        $this->fillFromProductGift($productGiftData, $productGift);

        return $productGiftData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductGift\ProductGiftData $productGiftData
     * @param \Shopsys\ShopBundle\Model\Product\ProductGift\ProductGift $productGift
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
     * @param \Shopsys\ShopBundle\Model\Product\ProductGift\ProductGiftData $productGiftData
     */
    private function fillNew(ProductGiftData $productGiftData): void
    {
        $productGiftData->active = false;
    }
}
