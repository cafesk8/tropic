<?php

declare(strict_types=1);

namespace App\Model\Product\Group;

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
}
