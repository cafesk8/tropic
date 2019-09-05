<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductVariantFacade as BaseProductVariantFacade;

class ProductVariantFacade extends BaseProductVariantFacade
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $mainProduct
     * @param \Shopsys\FrameworkBundle\Model\Product\Product[] $variants
     * @return \Shopsys\FrameworkBundle\Model\Product\Product
     */
    public function createVariant(Product $mainProduct, array $variants)
    {
        $mainVariant = parent::createVariant($mainProduct, $variants);
        $this->em->flush($mainProduct);

        return $mainVariant;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $variant
     */
    public function removeVariant(Product $variant): void
    {
        $mainVariant = $variant->getMainVariant();
        $variant->unsetMainVariant();

        $this->em->flush([$mainVariant, $variant]);

        if (count($mainVariant->getVariants()) === 0) {
            $this->em->remove($mainVariant);
            $this->em->flush();
        }
    }
}
