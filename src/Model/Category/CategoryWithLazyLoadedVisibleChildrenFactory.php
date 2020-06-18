<?php

declare(strict_types=1);

namespace App\Model\Category;

use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Category\CategoryWithLazyLoadedVisibleChildren;
use Shopsys\FrameworkBundle\Model\Category\CategoryWithLazyLoadedVisibleChildrenFactory as BaseCategoryWithLazyLoadedVisibleChildrenFactory;

/**
 * @property \App\Model\Category\CategoryRepository $categoryRepository
 * @method __construct(\App\Model\Category\CategoryRepository $categoryRepository)
 * @method \Shopsys\FrameworkBundle\Model\Category\CategoryWithLazyLoadedVisibleChildren[] createCategoriesWithLazyLoadedVisibleChildren(\App\Model\Category\Category[] $categories, \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig)
 */
class CategoryWithLazyLoadedVisibleChildrenFactory extends BaseCategoryWithLazyLoadedVisibleChildrenFactory
{
    /**
     * @param \App\Model\Category\Category[] $categories
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \Shopsys\FrameworkBundle\Model\Category\CategoryWithLazyLoadedVisibleChildren[]
     */
    public function createCategoriesWithLazyLoadedVisibleAndListableChildren($categories, DomainConfig $domainConfig)
    {
        $categoriesWithVisibleAndListableChildren = $this->categoryRepository->getCategoriesWithVisibleAndListableChildren($categories, $domainConfig->getId());

        $categoriesWithLazyLoadedVisibleAndListableChildren = [];
        foreach ($categories as $category) {
            if ($category->isSaleType()) {
                $categoriesWithLazyLoadedVisibleAndListableChildren[] = new CategoryWithLazyLoadedVisibleChildren(
                    function () use ($domainConfig) {
                        $categories = $this->categoryRepository->getAllVisibleAndListableSaleCategoriesByDomain($domainConfig);

                        return $this->createCategoriesWithLazyLoadedVisibleAndListableChildren($categories, $domainConfig);
                    },
                    $category,
                    true
                );
            } else {
                $hasChildren = in_array($category, $categoriesWithVisibleAndListableChildren, true);
                $categoriesWithLazyLoadedVisibleAndListableChildren[] = new CategoryWithLazyLoadedVisibleChildren(
                    function () use ($category, $domainConfig) {
                        $categories = $this->categoryRepository->getTranslatedVisibleAndListableSubcategoriesByDomain($category, $domainConfig);

                        return $this->createCategoriesWithLazyLoadedVisibleAndListableChildren($categories, $domainConfig);
                    },
                    $category,
                    $hasChildren
                );
            }
        }

        return $categoriesWithLazyLoadedVisibleAndListableChildren;
    }
}
