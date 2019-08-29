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

    /**
     * @param int $domainId
     * @return int|null
     */
    public function getHighestLegendaryCategoryIdByDomainId(int $domainId): ?int
    {
        return $this->categoryRepository->getHighestLegendaryCategoryIdByDomainId($domainId);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param int $domainId
     * @return string|null
     */
    public function findMallCategoryForProduct(Product $product, int $domainId): ?string
    {
        return $this->categoryRepository->findMallCategoryForProduct($product, $domainId);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Category\Category $destinationCategory
     * @return array
     */
    public function getCategoriesInPath(Category $destinationCategory): array
    {
        $categoriesInPathWithoutRoot = array_slice($this->categoryRepository->getPath($destinationCategory), 1);

        return $categoriesInPathWithoutRoot;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Category\Category $destinationCategory
     * @param string $locale
     * @param string $delimiter
     * @return string
     */
    public function getCategoriesNamesInPathAsString(Category $destinationCategory, string $locale, string $delimiter = '/'): string
    {
        $categoriesInPath = $this->getCategoriesInPath($destinationCategory);

        $categoriesNamesInPath = [];
        foreach ($categoriesInPath as $category) {
            $categoriesNamesInPath[] = $category->getName($locale);
        }

        return implode($delimiter, $categoriesNamesInPath);
    }
}
