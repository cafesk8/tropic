<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Search\Export;

use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\Search\Export\ProductSearchExportWithFilterRepository as BaseProductSearchExportWithFilterRepository;

class ProductSearchExportWithFilterRepository extends BaseProductSearchExportWithFilterRepository
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    protected $productFacade;

    /**
     * @param int $domainId
     * @param string $locale
     * @param int $startFrom
     * @param int $batchSize
     * @return array
     */
    public function getProductsData(int $domainId, string $locale, int $startFrom, int $batchSize): array
    {
        $queryBuilder = $this->createQueryBuilder($domainId, $locale)
            ->setFirstResult($startFrom)
            ->setMaxResults($batchSize);

        $query = $queryBuilder->getQuery();

        $result = [];
        /** @var \Shopsys\ShopBundle\Model\Product\Product $product */
        foreach ($query->getResult() as $product) {
            $variants = $this->productFacade->getVariantsForProduct($product, $domainId);

            $parameters = $this->extractParametersForProduct($product, $variants, $locale);
            $flagIds = $this->extractFlags($product);
            $categoryIds = $this->extractCategories($domainId, $product);
            $prices = $this->extractPrices($domainId, $product);

            $result[] = [
                'id' => $product->getId(),
                'catnum' => $product->getCatnum(),
                'partno' => $product->getPartno(),
                'ean' => $product->getEan(),
                'name' => $product->getName($locale),
                'description' => $product->getDescription($domainId),
                'short_description' => $product->getShortDescription($domainId),
                'brand' => $product->getBrand() ? $product->getBrand()->getId() : '',
                'flags' => $flagIds,
                'categories' => $categoryIds,
                'in_stock' => $product->getCalculatedAvailability()->getDispatchTime() === 0,
                'prices' => $prices,
                'action_price' => $product->getActionPrice($domainId) ? (float)$product->getActionPrice($domainId)->getAmount() : null,
                'parameters' => array_values($parameters),
                'ordering_priority' => $product->getOrderingPriority(),
                'calculated_selling_denied' => $product->getCalculatedSellingDenied(),
                'selling_from' => ($product->getSellingFrom() !== null) ? $product->getSellingFrom()->format('Y-m-d') : date('Y-m-d'),
            ];
        }

        return $result;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $variants
     * @param string $locale
     * @return array
     */
    private function extractParametersForProduct(Product $product, array $variants, string $locale): array
    {
        $parameters = [];
        foreach ($variants as $variant) {
            $parameters = array_merge($this->extractParameters($locale, $variant), $parameters);
        }

        $parameters = array_merge($this->extractParameters($locale, $product), $parameters);

        return array_unique($parameters, SORT_REGULAR);
    }
}
