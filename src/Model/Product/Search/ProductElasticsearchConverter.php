<?php

declare(strict_types=1);

namespace App\Model\Product\Search;

use App\Model\Product\Availability\AvailabilityData;
use App\Model\Product\Product;
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
        $result['set_items'] = $result['set_items'] ?? $result['group_items'] ?? [];
        $result['real_sale_stocks_quantity'] = $result['real_sale_stocks_quantity'] ?? 0;
        $result['is_in_any_sale_stock'] = $result['is_in_any_sale_stock'] ?? false;
        $result['prices']['is_default'] = $product['prices']['is_default'] ?? false;
        $result['prices']['is_standard'] = $product['prices']['is_standard'] ?? false;
        $result['prices']['is_sale'] = $product['prices']['is_sale'] ?? false;
        $result['pohoda_product_type'] = $result['pohoda_product_type'] ?? Product::POHODA_PRODUCT_TYPE_ID_SINGLE_PRODUCT;
        $result['internal_stocks_quantity'] = $result['internal_stocks_quantity'] ?? 0;
        $result['external_stocks_quantity'] = $result['external_stocks_quantity'] ?? 0;
        $result['warranty'] = $result['warranty'] ?? -1;
        $result['variant_type'] = $result['variant_type'] ?? Product::VARIANT_TYPE_NONE;
        $result['recommended'] = $result['recommended'] ?? false;
        $result['supplier_set'] = $result['supplier_set'] ?? false;
        $result['main_category_path'] = $result['main_category_path'] ?? '';
        $result['is_in_news'] = $result['is_in_news'] ?? false;
        $result['availability_color'] = $result['availability_color'] ?? AvailabilityData::DEFAULT_COLOR;
        $result['boosting_name'] = $result['boosting_name'] ?? '';
        $result['available'] = $result['available'] ?? true;
        $result['product_news_active_from'] = $result['product_news_active_from'] ?? null;

        return $result;
    }
}
