<?php

declare(strict_types=1);

namespace App\Model\Order\Item;

use App\Model\Order\ItemSourceStock\OrderItemSourceStockDataFactory;
use App\Model\Order\ItemSourceStock\OrderItemSourceStockFacade;
use App\Model\Product\Product;
use App\Model\Product\ProductFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Module\ModuleFacade;
use Shopsys\FrameworkBundle\Model\Module\ModuleList;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderProductFacade as BaseOrderProductFacade;
use Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculationScheduler;
use Shopsys\FrameworkBundle\Model\Product\ProductHiddenRecalculator;
use Shopsys\FrameworkBundle\Model\Product\ProductSellingDeniedRecalculator;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade;

/**
 * @method runRecalculationsAfterStockQuantityChange(\App\Model\Order\Item\OrderItem[] $orderProducts)
 * @method \App\Model\Order\Item\OrderItem[] getOrderProductsUsingStockFromOrderProducts(\App\Model\Order\Item\OrderItem[] $orderProducts)
 * @property \App\Model\Product\ProductSellingDeniedRecalculator $productSellingDeniedRecalculator
 * @property \App\Model\Product\ProductHiddenRecalculator $productHiddenRecalculator
 */
class OrderProductFacade extends BaseOrderProductFacade
{
    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Model\Order\ItemSourceStock\OrderItemSourceStockFacade
     */
    private $orderItemSourceStockFacade;

    /**
     * @var \App\Model\Order\ItemSourceStock\OrderItemSourceStockDataFactory
     */
    private $orderItemSourceStockDataFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\ProductHiddenRecalculator $productHiddenRecalculator
     * @param \App\Model\Product\ProductSellingDeniedRecalculator $productSellingDeniedRecalculator
     * @param \Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculationScheduler $productAvailabilityRecalculationScheduler
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade $productVisibilityFacade
     * @param \Shopsys\FrameworkBundle\Model\Module\ModuleFacade $moduleFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Order\ItemSourceStock\OrderItemSourceStockFacade $orderItemSourceStockFacade
     * @param \App\Model\Order\ItemSourceStock\OrderItemSourceStockDataFactory $orderItemSourceStockDataFactory
     */
    public function __construct(
        EntityManagerInterface $em,
        ProductHiddenRecalculator $productHiddenRecalculator,
        ProductSellingDeniedRecalculator $productSellingDeniedRecalculator,
        ProductAvailabilityRecalculationScheduler $productAvailabilityRecalculationScheduler,
        ProductVisibilityFacade $productVisibilityFacade,
        ModuleFacade $moduleFacade,
        ProductFacade $productFacade,
        OrderItemSourceStockFacade $orderItemSourceStockFacade,
        OrderItemSourceStockDataFactory $orderItemSourceStockDataFactory
    ) {
        parent::__construct($em, $productHiddenRecalculator, $productSellingDeniedRecalculator, $productAvailabilityRecalculationScheduler, $productVisibilityFacade, $moduleFacade);
        $this->productFacade = $productFacade;
        $this->orderItemSourceStockFacade = $orderItemSourceStockFacade;
        $this->orderItemSourceStockDataFactory = $orderItemSourceStockDataFactory;
    }

    /**
     * @param \App\Model\Order\Item\OrderItem[] $orderProducts
     */
    public function subtractOrderProductsFromStock(array $orderProducts)
    {
        if ($this->moduleFacade->isEnabled(ModuleList::PRODUCT_STOCK_CALCULATIONS)) {
            $orderProductsUsingStock = $this->getOrderProductsUsingStockFromOrderProducts($orderProducts);
            foreach ($orderProductsUsingStock as $orderProductUsingStock) {
                $product = $orderProductUsingStock->getProduct();
                $orderItemSourceStocksData = $this->subtractStockQuantity(
                    $product,
                    $orderProductUsingStock->getQuantity(),
                    $orderProductUsingStock->isSaleItem()
                );

                foreach ($orderItemSourceStocksData as $orderItemSourceStockData) {
                    $orderItemSourceStockData->orderItem = $orderProductUsingStock;
                    $this->orderItemSourceStockFacade->create($orderItemSourceStockData);
                }

                if ($product->isPohodaProductTypeSet()) {
                    foreach ($product->getProductSets() as $productSet) {
                        $this->subtractStockQuantity(
                            $productSet->getItem(),
                            $orderProductUsingStock->getQuantity() * $productSet->getItemCount(),
                            $orderProductUsingStock->isSaleItem()
                        );
                    }
                }
                if ($product->getRealSaleStocksQuantity() <= 0) {
                    $product->markForRefresh();
                }
                $this->productFacade->updateTotalProductStockQuantity($product);
            }
            $this->em->flush();
            $this->runRecalculationsAfterStockQuantityChange($orderProducts);
        }
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $quantity
     * @param bool $isSaleItem
     * @return \App\Model\Order\ItemSourceStock\OrderItemSourceStockData[]
     */
    private function subtractStockQuantity(Product $product, int $quantity, bool $isSaleItem): array
    {
        $orderItemSourceStocksData = [];
        $remainingQuantity = $quantity;
        $product->subtractStockQuantity($remainingQuantity);

        foreach ($product->getStoreStocks() as $productStoreStock) {
            $isSaleStock = $productStoreStock->getStore()->isSaleStock();
            $availableQuantity = $productStoreStock->getStockQuantity();

            if (($isSaleStock && !$isSaleItem) || (!$isSaleStock && $isSaleItem) || $availableQuantity < 1) {
                continue;
            }

            if ($remainingQuantity > $availableQuantity) {
                $productStoreStock->subtractStockQuantity($availableQuantity);
                $remainingQuantity -= $availableQuantity;
                $orderItemSourceStocksData[] = $this->orderItemSourceStockDataFactory->create($productStoreStock->getStore(), $availableQuantity);
            } else {
                $productStoreStock->subtractStockQuantity($remainingQuantity);
                $orderItemSourceStocksData[] = $this->orderItemSourceStockDataFactory->create($productStoreStock->getStore(), $remainingQuantity);
                break;
            }
        }

        return $orderItemSourceStocksData;
    }

    /**
     * @param \App\Model\Order\Item\OrderItem[] $orderProducts
     */
    public function addOrderProductsToStock(array $orderProducts)
    {
        if ($this->moduleFacade->isEnabled(ModuleList::PRODUCT_STOCK_CALCULATIONS)) {
            $orderProductsUsingStock = $this->getOrderProductsUsingStockFromOrderProducts($orderProducts);
            foreach ($orderProductsUsingStock as $orderProductUsingStock) {
                $product = $orderProductUsingStock->getProduct();
                $product->addStockQuantity($orderProductUsingStock->getQuantity());

                if ($product->isPohodaProductTypeSet()) {
                    foreach ($product->getProductSets() as $productSet) {
                        $productSet->getItem()->addStockQuantity($orderProductUsingStock->getQuantity() * $productSet->getItemCount());
                    }
                }
                $this->productFacade->updateTotalProductStockQuantity($product);
            }
            $this->em->flush();
            $this->runRecalculationsAfterStockQuantityChange($orderProducts);
        }
    }
}
