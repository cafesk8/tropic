<?php

declare(strict_types=1);

namespace App\Model\Product\StoreStock\Transfer;

use App\Component\Transfer\Logger\TransferLogger;
use App\Model\Product\Product;
use App\Model\Product\ProductData;
use App\Model\Product\ProductDataFactory;
use App\Model\Store\StoreFacade;

class StoreStockTransferMapper
{
    /**
     * @var \App\Model\Product\ProductDataFactory
     */
    private $productDataFactory;

    /**
     * @var \App\Model\Store\StoreFacade
     */
    private $storeFacade;

    /**
     * @param \App\Model\Product\ProductDataFactory $productDataFactory
     * @param \App\Model\Store\StoreFacade $storeFacade
     */
    public function __construct(
        ProductDataFactory $productDataFactory,
        StoreFacade $storeFacade
    ) {
        $this->productDataFactory = $productDataFactory;
        $this->storeFacade = $storeFacade;
    }

    /**
     * @param \App\Model\Product\StoreStock\Transfer\StoreStockTransferResponseItemData $productTransferResponseItemData
     * @param \App\Model\Product\Product $product
     * @param \App\Component\Transfer\Logger\TransferLogger $transferLogger
     * @param string $transferIdentifier
     * @return \App\Model\Product\ProductData
     */
    public function mapTransferDataToProductData(
        StoreStockTransferResponseItemData $productTransferResponseItemData,
        Product $product,
        TransferLogger $transferLogger,
        string $transferIdentifier
    ): ProductData {
        $productData = $this->productDataFactory->createFromProduct($product);

        foreach ($productTransferResponseItemData->getSitesQuantity() as $stockQuantity) {
            $store = $this->storeFacade->findByExternalNumber($stockQuantity->getSiteNumber());

            if ($store === null) {
                $transferLogger->addError(
                    sprintf('Store with external number `%s` not found while updating product store stock quantities for product with ID `%s`.', $stockQuantity->getSiteNumber(), $product->getId())
                );
                continue;
            }

            $productData->stockQuantityByStoreId[$store->getId()] = $stockQuantity->getQuantity();
        }

        return $productData;
    }
}
