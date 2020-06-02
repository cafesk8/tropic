<?php

declare(strict_types=1);

namespace App\Model\Order\Item;

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
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\ProductHiddenRecalculator $productHiddenRecalculator
     * @param \App\Model\Product\ProductSellingDeniedRecalculator $productSellingDeniedRecalculator
     * @param \Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculationScheduler $productAvailabilityRecalculationScheduler
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade $productVisibilityFacade
     * @param \Shopsys\FrameworkBundle\Model\Module\ModuleFacade $moduleFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        ProductHiddenRecalculator $productHiddenRecalculator,
        ProductSellingDeniedRecalculator $productSellingDeniedRecalculator,
        ProductAvailabilityRecalculationScheduler $productAvailabilityRecalculationScheduler,
        ProductVisibilityFacade $productVisibilityFacade,
        ModuleFacade $moduleFacade,
        ProductFacade $productFacade
    ) {
        parent::__construct($em, $productHiddenRecalculator, $productSellingDeniedRecalculator, $productAvailabilityRecalculationScheduler, $productVisibilityFacade, $moduleFacade);
        $this->productFacade = $productFacade;
    }

    /**
     * @param \App\Model\Order\Item\OrderItem[] $orderProducts
     */
    public function subtractOrderProductsFromStock(array $orderProducts)
    {
        if ($this->moduleFacade->isEnabled(ModuleList::PRODUCT_STOCK_CALCULATIONS)) {
            /** @var \App\Model\Order\Item\OrderItem[] $orderProductsUsingStock */
            $orderProductsUsingStock = $this->getOrderProductsUsingStockFromOrderProducts($orderProducts);
            foreach ($orderProductsUsingStock as $orderProductUsingStock) {
                $product = $orderProductUsingStock->getProduct();
                $product->subtractStockQuantity(
                    $orderProductUsingStock->getQuantity(),
                    $orderProductUsingStock->isSaleItem()
                );

                if ($product->isPohodaProductTypeGroup()) {
                    foreach ($product->getProductGroups() as $productGroup) {
                        $productGroup->getItem()->subtractStockQuantity(
                            $orderProductUsingStock->getQuantity() * $productGroup->getItemCount(),
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
     * @param \App\Model\Order\Item\OrderItem[] $orderProducts
     */
    public function addOrderProductsToStock(array $orderProducts)
    {
        if ($this->moduleFacade->isEnabled(ModuleList::PRODUCT_STOCK_CALCULATIONS)) {
            /** @var \App\Model\Order\Item\OrderItem[] $orderProductsUsingStock */
            $orderProductsUsingStock = $this->getOrderProductsUsingStockFromOrderProducts($orderProducts);
            foreach ($orderProductsUsingStock as $orderProductUsingStock) {
                $product = $orderProductUsingStock->getProduct();
                $product->addStockQuantity($orderProductUsingStock->getQuantity());

                if ($product->isPohodaProductTypeGroup()) {
                    foreach ($product->getProductGroups() as $productGroup) {
                        $productGroup->getItem()->addStockQuantity($orderProductUsingStock->getQuantity() * $productGroup->getItemCount());
                    }
                }
                $this->productFacade->updateTotalProductStockQuantity($product);
            }
            $this->em->flush();
            $this->runRecalculationsAfterStockQuantityChange($orderProducts);
        }
    }
}
