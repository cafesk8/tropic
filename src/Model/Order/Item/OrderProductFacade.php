<?php

declare(strict_types=1);

namespace App\Model\Order\Item;

use Shopsys\FrameworkBundle\Model\Module\ModuleList;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderProductFacade as BaseOrderProductFacade;

class OrderProductFacade extends BaseOrderProductFacade
{
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
                $product->subtractStockQuantity($orderProductUsingStock->getQuantity());

                if ($product->isPohodaProductTypeGroup()) {
                    foreach ($product->getProductGroups() as $productGroup) {
                        $productGroup->getItem()->subtractStockQuantity($orderProductUsingStock->getQuantity() * $productGroup->getItemCount());
                    }
                }
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
            }
            $this->em->flush();
            $this->runRecalculationsAfterStockQuantityChange($orderProducts);
        }
    }
}
