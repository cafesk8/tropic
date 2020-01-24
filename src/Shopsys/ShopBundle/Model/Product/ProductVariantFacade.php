<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductVariantFacade as BaseProductVariantFacade;

/**
 * @property \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
 * @property \Shopsys\ShopBundle\Model\Product\ProductDataFactory $productDataFactory
 * @property \Shopsys\ShopBundle\Component\Image\ImageFacade $imageFacade
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade, \Shopsys\ShopBundle\Model\Product\ProductDataFactory $productDataFactory, \Shopsys\ShopBundle\Component\Image\ImageFacade $imageFacade, \Shopsys\FrameworkBundle\Model\Product\ProductFactoryInterface $productFactory, \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler $productPriceRecalculationScheduler, \Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculationScheduler $productAvailabilityRecalculationScheduler, \Shopsys\FrameworkBundle\Model\Product\Search\Export\ProductSearchExportScheduler $productSearchExportScheduler)
 */
class ProductVariantFacade extends BaseProductVariantFacade
{
    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $mainProduct
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $variants
     * @return \Shopsys\ShopBundle\Model\Product\Product
     */
    public function createVariant(Product $mainProduct, array $variants)
    {
        $mainVariant = parent::createVariant($mainProduct, $variants);
        $this->em->flush($mainProduct);

        return $mainVariant;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $variant
     */
    public function removeVariant(Product $variant): void
    {
        $mainVariant = $variant->getMainVariant();
        $variant->unsetMainVariant();

        $this->em->flush([$mainVariant, $variant]);
    }
}
