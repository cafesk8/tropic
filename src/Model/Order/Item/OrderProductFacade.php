<?php

declare(strict_types=1);

namespace App\Model\Order\Item;

use App\Model\Order\ItemSourceStock\OrderItemSourceStockDataFactory;
use App\Model\Order\ItemSourceStock\OrderItemSourceStockFacade;
use App\Model\Product\Product;
use App\Model\Product\ProductFacade;
use App\Model\Product\SoldOutActions\ProductSoldOutActionsScheduler;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Module\ModuleFacade;
use Shopsys\FrameworkBundle\Model\Module\ModuleList;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderProductFacade as BaseOrderProductFacade;
use Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculationScheduler;
use Shopsys\FrameworkBundle\Model\Product\ProductHiddenRecalculator;
use Shopsys\FrameworkBundle\Model\Product\ProductSellingDeniedRecalculator;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade;

/**
 * @method \App\Model\Order\Item\OrderItem[] getOrderProductsUsingStockFromOrderProducts(\App\Model\Order\Item\OrderItem[] $orderProducts)
 * @property \App\Model\Product\ProductSellingDeniedRecalculator $productSellingDeniedRecalculator
 * @property \App\Model\Product\ProductHiddenRecalculator $productHiddenRecalculator
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
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

    private ProductSoldOutActionsScheduler $productSoldOutActionsScheduler;

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
     * @param \App\Model\Product\SoldOutActions\ProductSoldOutActionsScheduler $productSoldOutActionsScheduler
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
        OrderItemSourceStockDataFactory $orderItemSourceStockDataFactory,
        ProductSoldOutActionsScheduler $productSoldOutActionsScheduler
    ) {
        parent::__construct($em, $productHiddenRecalculator, $productSellingDeniedRecalculator, $productAvailabilityRecalculationScheduler, $productVisibilityFacade, $moduleFacade);
        $this->productFacade = $productFacade;
        $this->orderItemSourceStockFacade = $orderItemSourceStockFacade;
        $this->orderItemSourceStockDataFactory = $orderItemSourceStockDataFactory;
        $this->productSoldOutActionsScheduler = $productSoldOutActionsScheduler;
    }

    /**
     * @param \App\Model\Order\Item\OrderItem[] $orderProducts
     */
    public function subtractOrderProductsFromStock(array $orderProducts)
    {
        if ($this->moduleFacade->isEnabled(ModuleList::PRODUCT_STOCK_CALCULATIONS)) {
            $toFlush = [];
            $orderProductsUsingStock = $this->getOrderProductsUsingStockFromOrderProducts($orderProducts);
            foreach ($orderProductsUsingStock as $orderProductUsingStock) {
                $product = $orderProductUsingStock->getProduct();
                $product->markForAvailabilityRecalculation();
                $toFlush[] = $product;
                $orderItemSourceStocksData = $this->subtractStockQuantity(
                    $product,
                    $orderProductUsingStock->getQuantity(),
                    $orderProductUsingStock->isSaleItem(),
                    false
                );

                foreach ($orderItemSourceStocksData as $orderItemSourceStockData) {
                    $orderItemSourceStockData->orderItem = $orderProductUsingStock;
                }
                $this->orderItemSourceStockFacade->createMultiple($orderItemSourceStocksData);

                if ($product->isPohodaProductTypeSet()) {
                    foreach ($product->getProductSets() as $productSet) {
                        $setItem = $productSet->getItem();
                        $setItem->markForAvailabilityRecalculation();
                        $toFlush[] = $setItem;
                        $this->subtractStockQuantity(
                            $setItem,
                            $orderProductUsingStock->getQuantity() * $productSet->getItemCount(),
                            $orderProductUsingStock->isSaleItem(),
                            true
                        );
                    }
                }
                $this->productFacade->updateTotalProductStockQuantity($product, true);
            }
            if (count($toFlush) > 0) {
                $this->em->flush($toFlush);
            }
            $this->runRecalculationsAfterStockQuantityChange($orderProducts);
        }
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $quantity
     * @param bool $isSaleItem
     * @param bool $isSetItem
     * @return \App\Model\Order\ItemSourceStock\OrderItemSourceStockData[]
     */
    public function subtractStockQuantity(Product $product, int $quantity, bool $isSaleItem, bool $isSetItem): array
    {
        $orderItemSourceStocksData = [];
        $remainingQuantity = $quantity;
        $product->subtractStockQuantity($remainingQuantity);

        foreach ($product->getStoreStocks() as $productStoreStock) {
            $isSaleStock = $productStoreStock->getStore()->isSaleStock();
            $availableQuantity = $productStoreStock->getStockQuantity();

            if (!$isSetItem && (($isSaleStock && !$isSaleItem) || (!$isSaleStock && $isSaleItem) || $availableQuantity < 1)) {
                continue;
            }

            if ($remainingQuantity > $availableQuantity) {
                $productStoreStock->subtractStockQuantity($availableQuantity);
                $remainingQuantity -= $availableQuantity;
                $orderItemSourceStocksData[] = $this->orderItemSourceStockDataFactory->create($productStoreStock->getStore(), $availableQuantity);
                $product->markForAvailabilityRecalculation();
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

    /**
     * When a product is sold out, all the necessary recalculations are processed outside the main request transaction.
     * This is done to avoid the potential deadlocks on conflict with CRON modules that run in parallel.
     * "hidden" calculation is removed - the attribute is deprecated on this project since ac2363d07dbd6eacbd840a151c7aafab6b9a4d0c
     *
     * @param \App\Model\Order\Item\OrderItem[] $orderProducts
     */
    protected function runRecalculationsAfterStockQuantityChange(array $orderProducts)
    {
        $orderProductsUsingStock = $this->getOrderProductsUsingStockFromOrderProducts($orderProducts);
        foreach ($orderProductsUsingStock as $orderProductUsingStock) {
            $relevantProduct = $orderProductUsingStock->getProduct();
            if ($relevantProduct->getRealStockQuantity() <= 0) {
                $this->productSoldOutActionsScheduler->scheduleProduct($relevantProduct);
            }
        }
    }

    /**
     * Simulates the state of product's stocks and calculated availability as it would look without the quantity
     * that is currently in cart (minus one because we want to show the worst availability for current quantity
     * in cart and not for the next piece that could potentially get added)
     *
     * @param \App\Model\Product\Product $product
     * @param int $subtractQuantity
     * @return \App\Model\Product\Product
     */
    public function cloneProductWithSubtractedStocks(Product $product, int $subtractQuantity = 0): Product
    {
        $productClone = $product->cloneSelfAndStoreStocks();

        if ($subtractQuantity === 0) {
            return $productClone;
        }

        $this->subtractStockQuantity($productClone, $subtractQuantity - 1, false, false);

        if ($product->isPohodaProductTypeSet()) {
            foreach ($productClone->getProductSets() as $productSet) {
                $this->subtractStockQuantity(
                    $productSet->getItem(),
                    ($subtractQuantity - 1) * $productSet->getItemCount(),
                    false,
                    true
                );
            }
        }

        return $productClone;
    }
}
