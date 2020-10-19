<?php

declare(strict_types=1);

namespace App\Model\Product\SoldOutActions;

use App\Model\Product\Availability\ProductAvailabilityRecalculator;
use App\Model\Product\ProductSellingDeniedRecalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ProductSoldOutActionsListener
{
    private ProductSoldOutActionsScheduler $productSoldOutActionsScheduler;

    private ProductSellingDeniedRecalculator $productSellingDeniedRecalculator;

    private ProductAvailabilityRecalculator $productAvailabilityRecalculator;

    /**
     * @var \App\Component\EntityExtension\EntityManagerDecorator
     */
    private EntityManagerInterface $entityManager;

    /**
     * @param \App\Model\Product\SoldOutActions\ProductSoldOutActionsScheduler $productSoldOutActionsScheduler
     * @param \App\Model\Product\ProductSellingDeniedRecalculator $productSellingDeniedRecalculator
     * @param \App\Model\Product\Availability\ProductAvailabilityRecalculator $productAvailabilityRecalculator
     * @param \App\Component\EntityExtension\EntityManagerDecorator $entityManager
     */
    public function __construct(
        ProductSoldOutActionsScheduler $productSoldOutActionsScheduler,
        ProductSellingDeniedRecalculator $productSellingDeniedRecalculator,
        ProductAvailabilityRecalculator $productAvailabilityRecalculator,
        EntityManagerInterface $entityManager
    ) {
        $this->productSoldOutActionsScheduler = $productSoldOutActionsScheduler;
        $this->productSellingDeniedRecalculator = $productSellingDeniedRecalculator;
        $this->productAvailabilityRecalculator = $productAvailabilityRecalculator;
        $this->entityManager = $entityManager;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($event->isMasterRequest()) {
            $this->recalculate();
        }
    }

    private function recalculate(): void
    {
        $products = $this->productSoldOutActionsScheduler->getProductsToProcessAndClean();
        foreach ($products as $product) {
            $this->productSellingDeniedRecalculator->calculateSellingDeniedForProduct($product);
            $this->productAvailabilityRecalculator->recalculateOneProductAvailability($product);
            $product->markForVisibilityRecalculation();
            $product->markForRefresh();
            $this->entityManager->flush($product);
        }
    }
}
