<?php

declare(strict_types=1);

namespace App\Model\Order\Item;

use App\Model\Product\Group\ProductGroupFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Model\Module\ModuleFacade;
use Shopsys\FrameworkBundle\Model\Module\ModuleList;
use Shopsys\FrameworkBundle\Model\Order\Item\OrderProductFacade as BaseOrderProductFacade;
use Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculationScheduler;
use Shopsys\FrameworkBundle\Model\Product\ProductHiddenRecalculator;
use Shopsys\FrameworkBundle\Model\Product\ProductSellingDeniedRecalculator;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade;

class OrderProductFacade extends BaseOrderProductFacade
{
    /**
     * @var \App\Model\Product\Group\ProductGroupFacade
     */
    private $productGroupFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\ProductHiddenRecalculator $productHiddenRecalculator
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductSellingDeniedRecalculator $productSellingDeniedRecalculator
     * @param \Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculationScheduler $productAvailabilityRecalculationScheduler
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade $productVisibilityFacade
     * @param \Shopsys\FrameworkBundle\Model\Module\ModuleFacade $moduleFacade
     * @param \App\Model\Product\Group\ProductGroupFacade $productGroupFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        ProductHiddenRecalculator $productHiddenRecalculator,
        ProductSellingDeniedRecalculator $productSellingDeniedRecalculator,
        ProductAvailabilityRecalculationScheduler $productAvailabilityRecalculationScheduler,
        ProductVisibilityFacade $productVisibilityFacade,
        ModuleFacade $moduleFacade,
        ProductGroupFacade $productGroupFacade
    ) {
        parent::__construct(
            $em,
            $productHiddenRecalculator,
            $productSellingDeniedRecalculator,
            $productAvailabilityRecalculationScheduler,
            $productVisibilityFacade,
            $moduleFacade
        );
        $this->productGroupFacade = $productGroupFacade;
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
                $product->subtractStockQuantity($orderProductUsingStock->getQuantity());

                if ($product->isPohodaProductTypeGroup()) {
                    foreach ($this->productGroupFacade->getAllByMainProduct($product) as $productGroup) {
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
                    foreach ($this->productGroupFacade->getAllByMainProduct($product) as $productGroup) {
                        $productGroup->getItem()->addStockQuantity($orderProductUsingStock->getQuantity() * $productGroup->getItemCount());
                    }
                }
            }
            $this->em->flush();
            $this->runRecalculationsAfterStockQuantityChange($orderProducts);
        }
    }
}
