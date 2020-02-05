<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Search;

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
        $result['main_variant_group_products'] = $product['main_variant_group_products'] ?? [];
        $result['second_distinguishing_parameter_values'] = $product['second_distinguishing_parameter_values'] ?? [];
        $result['main_variant_id'] = $product['main_variant_id'] ?? null;
        $result['default_price'] = $result['default_price'] ?? [];

        return $result;
    }
}
