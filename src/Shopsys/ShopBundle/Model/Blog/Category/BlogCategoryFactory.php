<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Blog\Category;

class BlogCategoryFactory
{
    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryData $data
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory|null $rootBlogCategory
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory
     */
    public function create(BlogCategoryData $data, ?BlogCategory $rootBlogCategory): BlogCategory
    {
        $blogCategory = new BlogCategory($data);

        if ($rootBlogCategory !== null && $blogCategory->getParent() === null) {
            $blogCategory->setParent($rootBlogCategory);
        }

        return $blogCategory;
    }
}
