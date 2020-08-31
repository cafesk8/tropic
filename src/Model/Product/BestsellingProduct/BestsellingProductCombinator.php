<?php

declare(strict_types=1);

namespace App\Model\Product\BestsellingProduct;

use Shopsys\FrameworkBundle\Model\Product\BestsellingProduct\BestsellingProductCombinator as BaseBestsellingProductCombinator;

class BestsellingProductCombinator extends BaseBestsellingProductCombinator
{
    /**
     * @param \App\Model\Product\Product[] $manualProductsIndexedByPosition
     * @param \App\Model\Product\Product[] $automaticProducts
     * @param int $maxResults
     * @return \App\Model\Product\Product[]
     */
    public function combineManualAndAutomaticProducts(
        array $manualProductsIndexedByPosition,
        array $automaticProducts,
        $maxResults
    ) {
        $automaticProductsExcludingManual = $this->getAutomaticProductsExcludingManual(
            $automaticProducts,
            $manualProductsIndexedByPosition
        );
        $combinedProducts = $this->getCombinedProducts(
            $manualProductsIndexedByPosition,
            $automaticProductsExcludingManual,
            $maxResults
        );
        return $combinedProducts;
    }

    /**
     * @param \App\Model\Product\Product[] $automaticProducts
     * @param \App\Model\Product\Product[] $manualProducts
     * @return \App\Model\Product\Product[]
     */
    protected function getAutomaticProductsExcludingManual(
        array $automaticProducts,
        array $manualProducts
    ) {
        foreach ($manualProducts as $manualProduct) {
            $automaticProductKey = array_search($manualProduct, $automaticProducts, true);
            if ($automaticProductKey !== false) {
                unset($automaticProducts[$automaticProductKey]);
            }
        }

        return $automaticProducts;
    }

    /**
     * @param \App\Model\Product\Product[] $manualProductsIndexedByPosition
     * @param \App\Model\Product\Product[] $automaticProductsExcludingManual
     * @param int $maxResults
     * @return \App\Model\Product\Product[]
     */
    protected function getCombinedProducts(
        array $manualProductsIndexedByPosition,
        array $automaticProductsExcludingManual,
        $maxResults
    ) {
        $combinedProducts = [];
        for ($position = 0; $position < $maxResults; $position++) {
            if (array_key_exists($position, $manualProductsIndexedByPosition)) {
                $combinedProducts[] = $manualProductsIndexedByPosition[$position];
            } elseif (count($automaticProductsExcludingManual) > 0) {
                $combinedProducts[] = array_shift($automaticProductsExcludingManual);
            }
        }
        return $combinedProducts;
    }
}
