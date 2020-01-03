<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Category;

use Shopsys\FrameworkBundle\Model\Category\Category as BaseCategory;
use Shopsys\FrameworkBundle\Model\Category\CategoryData as BaseCategoryData;
use Shopsys\FrameworkBundle\Model\Category\CategoryDataFactory as BaseCategoryDataFactory;

class CategoryDataFactory extends BaseCategoryDataFactory
{
    /**
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
     * @return \Shopsys\ShopBundle\Model\Category\CategoryData
     */
    public function createFromCategory(BaseCategory $category): BaseCategoryData
    {
        $categoryData = new CategoryData();
        $this->fillFromCategory($categoryData, $category);

        return $categoryData;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Category\CategoryData
     */
    public function create(): BaseCategoryData
    {
        $categoryData = new CategoryData();
        $this->fillNew($categoryData);

        return $categoryData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Category\CategoryData $categoryData
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
     */
    protected function fillFromCategory(BaseCategoryData $categoryData, BaseCategory $category)
    {
        parent::fillFromCategory($categoryData, $category);

        $categoryData->displayedInHorizontalMenu = $category->isDisplayedInHorizontalMenu();
        $categoryData->listable = $category->isListable();
        $categoryData->preListingCategory = $category->isPreListingCategory();
        $categoryData->displayedInFirstColumn = $category->isDisplayedInFirstColumn();
        $categoryData->legendaryCategory = $category->isLegendaryCategory();
        $categoryData->mallCategoryId = $category->getMallCategoryId();
        $categoryData->leftBannerTexts = $category->getLeftBannerTexts();
        $categoryData->rightBannerTexts = $category->getRightBannerTexts();
    }
}
