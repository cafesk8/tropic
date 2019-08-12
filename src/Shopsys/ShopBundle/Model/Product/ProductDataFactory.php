<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\FrameworkBundle\Model\Product\ProductData as BaseProductData;
use Shopsys\FrameworkBundle\Model\Product\ProductDataFactory as BaseProductDataFactory;

class ProductDataFactory extends BaseProductDataFactory
{
    /**
     * @return \Shopsys\ShopBundle\Model\Product\ProductData
     */
    public function create(): BaseProductData
    {
        $productData = new ProductData();
        $this->fillNew($productData);

        return $productData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\ShopBundle\Model\Product\ProductData
     */
    public function createFromProduct(BaseProduct $product): BaseProductData
    {
        $productData = new ProductData();
        $this->fillFromProduct($productData, $product);

        return $productData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     */
    public function fillNew(BaseProductData $productData)
    {
        parent::fillNew($productData);
        $nullForAllDomains = $this->getNullForAllDomains();
        $productData->actionPrices = $nullForAllDomains;

        $productData->storeStocks = [];
        $productData->generateToHsSportXmlFeed = true;
        $productData->finished = false;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     */
    public function fillFromProduct(BaseProductData $productData, BaseProduct $product)
    {
        parent::fillFromProduct($productData, $product);

        foreach ($product->getStoreStocks() as $storeStock) {
            $productData->stockQuantityByStoreId[$storeStock->getStore()->getId()] = $storeStock->getStockQuantity();
        }

        if ($product->getMainVariantGroup() !== null) {
            $productData->productsInGroup = $product->getMainVariantGroup()->getProducts();
            $productData->distinguishingParameterForMainVariantGroup = $product->getMainVariantGroup()->getDistinguishingParameter();
        }

        foreach ($this->domain->getAllIds() as $domainId) {
            $productData->actionPrices[$domainId] = $product->getActionPrice($domainId);
        }

        $productData->transferNumber = $product->getTransferNumber();
        $productData->distinguishingParameter = $product->getDistinguishingParameter();
        $productData->mainVariantGroup = $product->getMainVariantGroup();
        $productData->gift = $product->getGift();
        $productData->generateToHsSportXmlFeed = $product->isGenerateToHsSportXmlFeed();
        $productData->finished = $product->isFinished();
        $productData->youtubeVideoId = $product->getYoutubeVideoId();
        $productData->mallExport = $product->isMallExport();
        $productData->mallExportedAt = $product->getMallExportedAt();
    }
}
