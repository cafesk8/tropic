<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Category;

use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Category\CategoryFacade as BaseCategoryFacade;
use Shopsys\FrameworkBundle\Model\Product\Product;

/**
 * @property \Shopsys\ShopBundle\Model\Category\CategoryWithLazyLoadedVisibleChildrenFactory $categoryWithLazyLoadedVisibleChildrenFactory
 * @method \Shopsys\ShopBundle\Model\Category\Category getRootCategory()
 */
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
    public function getAllVisibleAndListableCategoriesForFirstColumnByDomainId(int $domainId): array
    {
        return $this->categoryRepository->getAllVisibleCategoriesForFirstColumnByDomainId($domainId);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Category\Category[]
     */
    public function getAllVisibleAndListableChildrenByCategoryAndDomainId(Category $category, int $domainId): array
    {
        return $this->categoryRepository->getAllVisibleAndListableChildrenByCategoryAndDomainId($category, $domainId);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Category\Category $parentCategory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \Shopsys\FrameworkBundle\Model\Category\CategoryWithLazyLoadedVisibleChildren[]
     */
    public function getCategoriesWithLazyLoadedVisibleAndListableChildrenForParent(Category $parentCategory, DomainConfig $domainConfig): array
    {
        $categories = $this->categoryRepository->getTranslatedVisibleAndListableSubcategoriesByDomain($parentCategory, $domainConfig);

        $categoriesWithLazyLoadedVisibleAndListableChildren = $this->categoryWithLazyLoadedVisibleChildrenFactory
            ->createCategoriesWithLazyLoadedVisibleAndListableChildren($categories, $domainConfig);

        return $categoriesWithLazyLoadedVisibleAndListableChildren;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomain[]
     */
    public function getProductVisibleAndListableProductCategoryDomains(Product $product, int $domainId): array
    {
        return $this->categoryRepository->getProductVisibleAndListableProductCategoryDomains($product, $domainId);
    }

    /**
     * @param string|null $searchText
     * @param int $limit
     * @return \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult
     */
    public function getSearchAutocompleteCategories($searchText, $limit)
    {
        $page = 1;

        $paginationResult = $this->categoryRepository->getPaginationResultForSearchVisibleAndListable(
            $searchText,
            $this->domain->getId(),
            $this->domain->getLocale(),
            $page,
            $limit
        );

        return $paginationResult;
    }

    /**
     * @param int $domainId
     * @param string $locale
     * @param string|null $searchText
     * @return \Shopsys\FrameworkBundle\Model\Category\Category[]
     */
    public function getVisibleAndListableByDomainAndSearchText(int $domainId, string $locale, ?string $searchText): array
    {
        $categories = $this->categoryRepository->getVisibleAndListableByDomainIdAndSearchText(
            $domainId,
            $locale,
            $searchText
        );

        return $categories;
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
