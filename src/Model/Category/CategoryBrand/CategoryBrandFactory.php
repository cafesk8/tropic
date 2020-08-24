<?php

declare(strict_types=1);

namespace App\Model\Category\CategoryBrand;

use App\Model\Category\Category;
use App\Model\Product\Brand\Brand;

class CategoryBrandFactory
{
    /**
     * @param \App\Model\Category\Category $category
     * @param \App\Model\Product\Brand\Brand $brand
     * @param int $priority
     * @return \App\Model\Category\CategoryBrand\CategoryBrand
     */
    public function create(Category $category, Brand $brand, int $priority): CategoryBrand
    {
        return new CategoryBrand($category, $brand, $priority);
    }
}
