<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Category;

use Shopsys\FrameworkBundle\Model\Category\CategoryFacade as BaseCategoryFacade;
use Shopsys\FrameworkBundle\Model\Product\Product;

class CategoryFacade extends BaseCategoryFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Category\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @return \Shopsys\ShopBundle\Model\Category\Category[]
     */
    public function getAll(): array
    {
        return $this->categoryRepository->getAll();
    }

    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Category\Category[]
     */
    public function getAllVisibleCategoriesForFirstColumnByDomainId(int $domainId): array
    {
        return $this->categoryRepository->getAllVisibleCategoriesForFirstColumnByDomainId($domainId);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomain[]
     */
    public function getProductVisibleProductCategoryDomains(Product $product, int $domainId): array
    {
        return $this->categoryRepository->getProductVisibleProductCategoryDomains($product, $domainId);
    }
}
