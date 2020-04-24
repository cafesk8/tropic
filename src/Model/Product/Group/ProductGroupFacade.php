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
}
