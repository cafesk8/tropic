<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Domain\DomainHelper;
use App\Component\Transfer\Pohoda\Product\PohodaProduct;
use App\Model\Product\ProductData;
use Shopsys\FrameworkBundle\Component\String\TransformString;

class PohodaProductMapper
{
    /**
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProduct $pohodaProduct
     * @param \App\Model\Product\ProductData $productData
     */
    public function mapPohodaProductToProductData(
        PohodaProduct $pohodaProduct,
        ProductData $productData
    ): void {
        $productData->pohodaId = $pohodaProduct->pohodaId;
        $productData->catnum = $pohodaProduct->catnum;
        $productData->name[DomainHelper::CZECH_LOCALE] = TransformString::emptyToNull($pohodaProduct->name);
        $productData->name[DomainHelper::SLOVAK_LOCALE] = TransformString::emptyToNull($pohodaProduct->nameSk);
        $productData->shortDescriptions[DomainHelper::CZECH_DOMAIN] = $pohodaProduct->shortDescription;
        $productData->descriptions[DomainHelper::CZECH_DOMAIN] = $pohodaProduct->longDescription;
        $productData->usingStock = true;
        $productData->registrationDiscountDisabled = $pohodaProduct->registrationDiscountDisabled;
    }
}
