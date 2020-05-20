<?php

declare(strict_types=1);

namespace App\Model\Product\Search;

use Shopsys\FrameworkBundle\Model\Product\Search\ProductElasticsearchConverter as BaseProductElasticsearchConverter;

class ProductElasticsearchConverter extends BaseProductElasticsearchConverter
{
    /**
     * @param array $product
     * @return array
     */
    public function fillEmptyFields(array $product): array
    {
        $result = parent::fillEmptyFields($product);
        $price = $result['prices']['amount'] ?? 0;
        $result['prices']['price_with_vat'] = $product['prices']['price_with_vat'] ?? $price;
        $result['prices']['price_without_vat'] = $product['prices']['price_without_vat'] ?? $price;
        $result['prices']['vat'] = $product['prices']['vat'] ?? 0;
        $result['prices']['price_from'] = $product['prices']['price_from'] ?? false;
        $result['main_variant_id'] = $product['main_variant_id'] ?? null;
        $result['minimum_amount'] = $product['minimum_amount'] ?? 1;
        $result['amount_multiplier'] = $product['amount_multiplier'] ?? 1;
        $result['variants_aliases'] = $result['variants_aliases'] ?? [];
        $result['prices_for_filter'] = $result['prices_for_filter'] ?? [];
        $result['group_items'] = $result['group_items'] ?? [];
        $result['real_sale_stocks_quantity'] = $result['real_sale_stocks_quantity'] ?? 0;
        $result['is_in_any_sale_stock'] = $result['is_in_any_sale_stock'] ?? false;
        $result['prices']['is_default'] = $product['prices']['is_default'] ?? false;
        $result['prices']['is_standard'] = $product['prices']['is_standard'] ?? false;

        return $result;
    }
}
