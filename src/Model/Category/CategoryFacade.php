<?php

declare(strict_types=1);

namespace App\Model\Category;

use App\Model\Advert\Advert;
use App\Model\Category\Transfer\CategoryRemoveFacade;
use App\Model\Category\Transfer\Exception\MaximumPercentageOfCategoriesToRemoveLimitExceeded;
use App\Model\Product\Parameter\Parameter;
use App\Model\Product\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Component\Plugin\PluginCrudExtensionFacade;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\FrameworkBundle\Model\Category\CategoryFacade as BaseCategoryFacade;
use Shopsys\FrameworkBundle\Model\Category\CategoryFactoryInterface;
use Shopsys\FrameworkBundle\Model\Category\CategoryRepository;
use Shopsys\FrameworkBundle\Model\Category\CategoryVisibilityRecalculationScheduler;
use Shopsys\FrameworkBundle\Model\Category\CategoryWithLazyLoadedVisibleChildrenFactory;
use Shopsys\FrameworkBundle\Model\Category\CategoryWithPreloadedChildrenFactory;
use Shopsys\FrameworkBundle\Model\Category\Exception\CategoryNotFoundException;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade;

/**
 * @property \App\Model\Category\CategoryWithLazyLoadedVisibleChildrenFactory $categoryWithLazyLoadedVisibleChildrenFactory
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 * @method \App\Model\Category\Category getRootCategory()
 * @property \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
 * @property \App\Component\Image\ImageFacade $imageFacade
 * @method \App\Model\Category\Category getById(int $categoryId)
 * @method \App\Model\Category\Category create(\App\Model\Category\CategoryData $categoryData)
 * @method \App\Model\Category\Category edit(int $categoryId, \App\Model\Category\CategoryData $categoryData)
 * @method \App\Model\Category\Category[] getTranslatedAll(\Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig)
 * @method \App\Model\Category\Category[] getAllCategoriesOfCollapsedTree(\App\Model\Category\Category[] $selectedCategories)
 * @method \App\Model\Category\Category[] getFullPathsIndexedByIdsForDomain(int $domainId, string $locale)
 * @method \App\Model\Category\Category[] getVisibleCategoriesInPathFromRootOnDomain(\App\Model\Category\Category $category, int $domainId)
 * @method \Shopsys\FrameworkBundle\Model\Category\CategoryWithLazyLoadedVisibleChildren[] getCategoriesWithLazyLoadedVisibleChildrenForParent(\App\Model\Category\Category $parentCategory, \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig)
 * @method \App\Model\Category\Category[] getVisibleByDomainAndSearchText(int $domainId, string $locale, string $searchText)
 * @method \App\Model\Category\Category[] getAllVisibleChildrenByCategoryAndDomainId(\App\Model\Category\Category $category, int $domainId)
 * @method \App\Model\Category\Category[] getTranslatedAllWithoutBranch(\App\Model\Category\Category $category, \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig)
 * @method \App\Model\Category\Category[]|null[] getProductMainCategoriesIndexedByDomainId(\App\Model\Product\Product $product)
 * @method \App\Model\Category\Category getProductMainCategoryByDomainId(\App\Model\Product\Product $product, int $domainId)
 * @method \App\Model\Category\Category|null findProductMainCategoryByDomainId(\App\Model\Product\Product $product, int $domainId)
 * @method string[] getCategoryNamesInPathFromRootToProductMainCategoryOnDomain(\App\Model\Product\Product $product, \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig)
 * @method \App\Model\Category\Category getVisibleOnDomainById(int $domainId, int $categoryId)
 * @method int[] getListableProductCountsIndexedByCategoryId(\App\Model\Category\Category[] $categories, \App\Model\Pricing\Group\PricingGroup $pricingGroup, int $domainId)
 * @method \App\Model\Category\Category getByUuid(string $categoryUuid)
 */
class CategoryFacade extends BaseCategoryFacade
{
    /**
     * @var \App\Model\Category\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade
     */
    protected $productVisibilityFacade;

    /**
     * @var \App\Model\Category\CategoryDataFactory
     */
    private $categoryDataFactory;

    /**
     * @var \App\Model\Product\ProductRepository
     */
    private $productRepository;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Category\CategoryRepository $categoryRepository
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Category\CategoryVisibilityRecalculationScheduler $categoryVisibilityRecalculationScheduler
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \Shopsys\FrameworkBundle\Component\Plugin\PluginCrudExtensionFacade $pluginCrudExtensionFacade
     * @param \Shopsys\FrameworkBundle\Model\Category\CategoryWithPreloadedChildrenFactory $categoryWithPreloadedChildrenFactory
     * @param \App\Model\Category\CategoryWithLazyLoadedVisibleChildrenFactory $categoryWithLazyLoadedVisibleChildrenFactory
     * @param \Shopsys\FrameworkBundle\Model\Category\CategoryFactoryInterface $categoryFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade $productVisibilityFacade
     * @param \App\Model\Category\CategoryDataFactory $categoryDataFactory
     * @param \App\Model\Product\ProductRepository $productRepository
     */
    public function __construct(
        EntityManagerInterface $em,
        CategoryRepository $categoryRepository,
        Domain $domain,
        CategoryVisibilityRecalculationScheduler $categoryVisibilityRecalculationScheduler,
        FriendlyUrlFacade $friendlyUrlFacade,
        ImageFacade $imageFacade,
        PluginCrudExtensionFacade $pluginCrudExtensionFacade,
        CategoryWithPreloadedChildrenFactory $categoryWithPreloadedChildrenFactory,
        CategoryWithLazyLoadedVisibleChildrenFactory $categoryWithLazyLoadedVisibleChildrenFactory,
        CategoryFactoryInterface $categoryFactory,
        ProductVisibilityFacade $productVisibilityFacade,
        CategoryDataFactory $categoryDataFactory,
        ProductRepository $productRepository
    ) {
        parent::__construct($em, $categoryRepository, $domain, $categoryVisibilityRecalculationScheduler, $friendlyUrlFacade, $imageFacade, $pluginCrudExtensionFacade, $categoryWithPreloadedChildrenFactory, $categoryWithLazyLoadedVisibleChildrenFactory, $categoryFactory);
        $this->productVisibilityFacade = $productVisibilityFacade;
        $this->categoryDataFactory = $categoryDataFactory;
        $this->productRepository = $productRepository;
    }

    /**
     * @return \App\Model\Category\Category[]
     */
    public function getAll(): array
    {
        return $this->categoryRepository->getAll();
    }

    /**
     * @param \App\Model\Category\Category $category
     * @param int $domainId
     * @return \App\Model\Category\Category[]
     */
    public function getAllVisibleAndListableChildrenByCategoryAndDomainId(Category $category, int $domainId): array
    {
        return $this->categoryRepository->getAllVisibleAndListableChildrenByCategoryAndDomainId($category, $domainId);
    }

    /**
     * @param \App\Model\Category\Category $parentCategory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \Shopsys\FrameworkBundle\Model\Category\CategoryWithLazyLoadedVisibleChildren[]
     */
    public function getCategoriesWithLazyLoadedVisibleAndListableChildrenForParent(
        Category $parentCategory,
        DomainConfig $domainConfig
    ): array {
        $categories = $this->categoryRepository->getTranslatedVisibleAndListableSubcategoriesByDomain(
            $parentCategory,
            $domainConfig
        );

        $categoriesWithLazyLoadedVisibleAndListableChildren = $this->categoryWithLazyLoadedVisibleChildrenFactory
            ->createCategoriesWithLazyLoadedVisibleAndListableChildren($categories, $domainConfig);

        return $categoriesWithLazyLoadedVisibleAndListableChildren;
    }

    /**
     * @param \App\Model\Product\Product $product
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
     * @return \App\Model\Category\Category[]
     */
    public function getVisibleAndListableByDomainAndSearchText(
        int $domainId,
        string $locale,
        ?string $searchText
    ): array {
        $categories = $this->categoryRepository->getVisibleAndListableByDomainIdAndSearchText(
            $domainId,
            $locale,
            $searchText
        );

        return $categories;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @return string|null
     */
    public function findMallCategoryForProduct(Product $product, int $domainId): ?string
    {
        return $this->categoryRepository->findMallCategoryForProduct(
            $product->isVariant() ? $product->getMainVariant() : $product,
            $domainId
        );
    }

    /**
     * @param \App\Model\Category\Category $destinationCategory
     * @return \App\Model\Category\Category[]
     */
    public function getCategoriesInPath(Category $destinationCategory): array
    {
        $categoriesInPathWithoutRoot = array_slice($this->categoryRepository->getPath($destinationCategory), 1);

        return $categoriesInPathWithoutRoot;
    }

    /**
     * @param \App\Model\Category\Category $destinationCategory
     * @param string $locale
     * @param string $delimiter
     * @return string
     */
    public function getCategoriesNamesInPathAsString(
        Category $destinationCategory,
        string $locale,
        string $delimiter = '/'
    ): string {
        $categoriesInPath = $this->getCategoriesInPath($destinationCategory);

        $categoriesNamesInPath = [];
        foreach ($categoriesInPath as $category) {
            $categoriesNamesInPath[] = $category->getName($locale);
        }

        return implode($delimiter, $categoriesNamesInPath);
    }

    /**
     * @param \App\Model\Advert\Advert $advert
     * @param \App\Model\Category\Category[] $newCategories
     */
    public function removeAdvertFromCategories(Advert $advert, array $newCategories): void
    {
        $this->categoryRepository->removeAdvertFromCategories($advert, $newCategories);
    }

    /**
     * @param \App\Model\Advert\Advert $advert
     * @return \App\Model\Category\Category[]
     */
    public function getCategoriesByAdvert(Advert $advert): array
    {
        return $this->categoryRepository->getCategoriesByAdvert($advert);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param string $separator
     * @return string|null
     */
    public function getCategoryFullPath(Product $product, DomainConfig $domainConfig, string $separator): ?string
    {
        $mainCategory = $this->findProductMainCategoryByDomainId($product, $domainConfig->getId());

        if ($mainCategory === null) {
            return null;
        }

        $categories = $this->getVisibleCategoriesInPathFromRootOnDomain(
            $mainCategory,
            $domainConfig->getId()
        );

        $categoryFullPath = null;
        $categoryNames = [];
        foreach ($categories as $category) {
            $categoryNames[] = $category->getName($domainConfig->getLocale());
        }

        return $categoryFullPath ?? implode($separator, $categoryNames);
    }

    /**
     * @return int[]
     */
    public function getCategoriesForOrderRecalculation(): array
    {
        $pohodaCategoriesIndexedByPohodaParent = $this->categoryRepository->getAllIndexedByIdGroupedByPohodaParentId();
        $categoriesForOrderRecalculation = [];

        foreach ($pohodaCategoriesIndexedByPohodaParent as $parentCategoryPohodaId => $categories) {
            $categoryParentCategory = $this->categoryRepository->findByPohodaId($parentCategoryPohodaId);

            if ($categoryParentCategory === null) {
                $parentCategoryId = null;
            } else {
                $parentCategoryId = $categoryParentCategory->getId();
            }

            foreach ($categories as $category) {
                $categoriesForOrderRecalculation[$category->getId()] = $parentCategoryId;
            }
        }
        return $categoriesForOrderRecalculation;
    }

    /**
     * @param int $pohodaId
     * @return \App\Model\Category\Category|null
     */
    public function findByPohodaId(int $pohodaId): ?Category
    {
        return $this->categoryRepository->findByPohodaId($pohodaId);
    }

    /**
     * @param array $pohodaIds
     * @return \App\Model\Category\Category[]
     */
    public function removeCategoriesExceptPohodaIds(array $pohodaIds): array
    {
        $allCategories = $this->categoryRepository->getAll();
        $categories = $this->categoryRepository->getCategoriesExceptPohodaIds($pohodaIds);

        $categoriesToRemovePercentage = (count($categories) / count($allCategories)) * 100;
        if ($categoriesToRemovePercentage > CategoryRemoveFacade::MAX_BATCH_CATEGORIES_REMOVE_PERCENT) {
            throw new MaximumPercentageOfCategoriesToRemoveLimitExceeded(
                sprintf(
                    'Trying to remove %s categories, which is %s percent of whole category tree, removing aborted. Maximum is %s percent.',
                    count($categories),
                    $categoriesToRemovePercentage,
                    CategoryRemoveFacade::MAX_BATCH_CATEGORIES_REMOVE_PERCENT
                )
            );
        }

        foreach ($categories as $category) {
            $this->deleteById($category->getId());
        }

        return $categories;
    }

    /**
     * see https://github.com/shopsys/shopsys/issues/1763
     * @param int $categoryId
     */
    public function deleteById($categoryId)
    {
        $category = $this->categoryRepository->getById($categoryId);
        $this->productVisibilityFacade->markProductsForRecalculationAffectedByCategory($category);

        parent::deleteById($categoryId);
    }

    /**
     * @return \App\Model\Category\Category
     */
    public function getSaleCategory(): Category
    {
        $category = $this->categoryRepository->findByType(Category::SALE_TYPE);

        if ($category === null) {
            throw new CategoryNotFoundException('Category with type "' . Category::SALE_TYPE . '" was not found!');
        }

        return $category;
    }

    /**
     * @return \App\Model\Category\Category|null
     */
    public function findNewsCategory(): ?Category
    {
        return $this->categoryRepository->findByType(Category::NEWS_TYPE);
    }

    /**
     * @return \App\Model\Category\Category
     */
    public function getNewsCategory(): Category
    {
        $category = $this->findNewsCategory();

        if ($category === null) {
            throw new CategoryNotFoundException('Category with type "' . Category::NEWS_TYPE . '" was not found!');
        }

        return $category;
    }

    public function refreshSpecialCategoriesVisibility(): void
    {
        try {
            $specialCategories = [
                $this->getSaleCategory(),
                $this->getNewsCategory(),
            ];
        } catch (CategoryNotFoundException $categoryNotFoundException) {
            return;
        }

        foreach ($specialCategories as $specialCategory) {
            $editCategory = false;
            $categoryData = $this->categoryDataFactory->createFromCategory($specialCategory);

            foreach ($this->domain->getAllIds() as $domainId) {
                $previousStateOfEnabled = $categoryData->enabled[$domainId];
                if (count($this->productRepository->getListableInCategoryIndependentOfPricingGroup($domainId, $specialCategory)) > 0) {
                    $categoryData->enabled[$domainId] = true;
                } else {
                    $categoryData->enabled[$domainId] = false;
                }
                if ($categoryData->enabled[$domainId] !== $previousStateOfEnabled) {
                    $editCategory = true;
                }
            }

            if ($editCategory === true) {
                $this->edit($specialCategory->getId(), $categoryData);
            }
        }
    }

    /**
     * @param \App\Model\Product\Parameter\Parameter $parameter
     */
    public function addParameterToAllCategories(Parameter $parameter): void
    {
        foreach ($this->getAll() as $category) {
            $categoryData = $this->categoryDataFactory->createFromCategory($category);
            $categoryData->filterParameters[] = $parameter;
            $this->edit($category->getId(), $categoryData);
        }
    }

    public function markSaleCategories(): void
    {
        $this->categoryRepository->markSaleCategories();
    }
}
