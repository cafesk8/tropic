<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\StoreStock\Transfer;

use Shopsys\ShopBundle\Component\Transfer\Logger\TransferLogger;
use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductData;
use Shopsys\ShopBundle\Model\Product\ProductDataFactory;
use Shopsys\ShopBundle\Model\Store\StoreFacade;

class StoreStockTransferMapper
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductDataFactory
     */
    private $productDataFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Store\StoreFacade
     */
    private $storeFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductDataFactory $productDataFactory
     * @param \Shopsys\ShopBundle\Model\Store\StoreFacade $storeFacade
     */
    public function __construct(
        ProductDataFactory $productDataFactory,
        StoreFacade $storeFacade
    ) {
        $this->productDataFactory = $productDataFactory;
        $this->storeFacade = $storeFacade;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\StoreStock\Transfer\StoreStockTransferResponseItemData $productTransferResponseItemData
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Shopsys\ShopBundle\Component\Transfer\Logger\TransferLogger $transferLogger
     * @param string $importType
     * @return \Shopsys\ShopBundle\Model\Product\ProductData
     */
    public function mapTransferDataToProductData(
        StoreStockTransferResponseItemData $productTransferResponseItemData,
        Product $product,
        TransferLogger $transferLogger,
        string $importType
    ): ProductData {
        $productData = $this->productDataFactory->createFromProduct($product);

        if ($importType === StoreStockImportCronModule::IMPORT_TYPE_IMPORT_ALL) {
            $productData->stockQuantityByStoreId = [];
        }

        $productData->stockQuantity = 0;
        foreach ($productTransferResponseItemData->getSitesQuantity() as $stockQuantity) {
            $store = $this->storeFacade->findByExternalNumber($stockQuantity->getSiteNumber());

            if ($store === null) {
                $transferLogger->addError(
                    sprintf('Store with external number `%s` not found while updating product store stock quantities.', $stockQuantity->getSiteNumber())
                );
                continue;
            }

            $productData->stockQuantityByStoreId[$store->getId()] = $stockQuantity->getQuantity();
            $productData->stockQuantity += $stockQuantity->getQuantity();
        }

        return $productData;
    }
}
