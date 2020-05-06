<?php

declare(strict_types=1);

namespace App\Model\Product\Group;

use App\Component\Transfer\Pohoda\Product\PohodaProductExportRepository;
use App\Model\Product\Product;

class ProductGroupFacade
{
    /**
     * @var \App\Model\Product\Group\ProductGroupRepository
     */
    private $productGroupRepository;

    /**
     * @param \App\Model\Product\Group\ProductGroupRepository $productGroupRepository
     */
    public function __construct(ProductGroupRepository $productGroupRepository)
    {
        $this->productGroupRepository = $productGroupRepository;
    }

    /**
     * @param \App\Model\Product\Product $mainProduct
     * @return \App\Model\Product\Group\ProductGroup[]
     */
    public function getAllByMainProduct(Product $mainProduct): array
    {
        return $this->productGroupRepository->getAllByMainProduct($mainProduct);
    }

    /**
     * @param \App\Model\Product\Product $item
     * @return \App\Model\Product\Group\ProductGroup[]
     */
    public function getAllByItem(Product $item): array
    {
        return $this->productGroupRepository->getAllByItem($item);
    }

    /**
     * @param \App\Model\Product\Product $mainProduct
     * @param string $locale
     * @return array[]
     */
    public function getAllForElasticByMainProduct(Product $mainProduct, string $locale): array
    {
        return array_map(function (ProductGroup $productGroup) use ($locale) {
            return [
                'id' => $productGroup->getItem()->getId(),
                'name' => $productGroup->getItem()->getName($locale),
                'amount' => $productGroup->getItemCount(),
            ];
        }, $this->getAllByMainProduct($mainProduct));
    }

    /**
     * @param \App\Model\Product\Group\ProductGroup $productGroup
     * @return int
     */
    public function getStockQuantity(ProductGroup $productGroup): int
    {
        $quantity = 0;

        foreach ($productGroup->getItem()->getStoreStocks() as $storeStock) {
            if (!in_array((int)$storeStock->getStore()->getExternalNumber(), [
                PohodaProductExportRepository::POHODA_STOCK_TROPIC_ID,
                PohodaProductExportRepository::POHODA_STOCK_EXTERNAL_ID,
            ], true)) {
                continue;
            }

            $quantity += $storeStock->getStockQuantity();
        }

        return (int)floor($quantity / $productGroup->getItemCount());
    }
}
