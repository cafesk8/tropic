<?php

declare(strict_types=1);

namespace App\Model\Product;

use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductVariantFacade as BaseProductVariantFacade;

/**
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 * @property \App\Model\Product\ProductFacade $productFacade
 * @property \App\Model\Product\ProductDataFactory $productDataFactory
 * @property \App\Component\Image\ImageFacade $imageFacade
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \App\Model\Product\ProductFacade $productFacade, \App\Model\Product\ProductDataFactory $productDataFactory, \App\Component\Image\ImageFacade $imageFacade, \Shopsys\FrameworkBundle\Model\Product\ProductFactoryInterface $productFactory, \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler $productPriceRecalculationScheduler, \Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculationScheduler $productAvailabilityRecalculationScheduler, \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportScheduler $productExportScheduler)
 */
class ProductVariantFacade extends BaseProductVariantFacade
{
    /**
     * @param \App\Model\Product\Product $mainProduct
     * @param \App\Model\Product\Product[] $variants
     * @return \App\Model\Product\Product
     */
    public function createVariant(Product $mainProduct, array $variants)
    {
        /** @var \App\Model\Product\Product $mainVariant */
        $mainVariant = parent::createVariant($mainProduct, $variants);
        $this->em->flush($mainProduct);
        $this->scheduleForImmediateExport(array_merge([$mainProduct, $mainVariant], $variants));

        return $mainVariant;
    }

    /**
     * @param \App\Model\Product\Product $variant
     */
    public function removeVariant(Product $variant): void
    {
        $mainVariant = $variant->getMainVariant();
        $variant->unsetMainVariant();

        $this->em->flush([$mainVariant, $variant]);
        $this->scheduleForImmediateExport([$mainVariant, $variant]);
    }

    /**
     * @param \App\Model\Product\Product[] $products
     */
    private function scheduleForImmediateExport(array $products): void
    {
        foreach ($products as $product) {
            $this->productExportScheduler->scheduleRowIdForImmediateExport($product->getId());
        }
    }
}
