<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Category\CategoryBlogArticle;

use Shopsys\ShopBundle\Model\Blog\Article\BlogArticle;
use Shopsys\ShopBundle\Model\Category\Category;

class CategoryBlogArticleFactory
{
    /**
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle $blogArticle
     * @param int $position
     * @return \Shopsys\ShopBundle\Model\Category\CategoryBlogArticle\CategoryBlogArticle
     */
    public function create(Category $category, BlogArticle $blogArticle, int $position): CategoryBlogArticle
    {
        return new CategoryBlogArticle($category, $blogArticle, $position);
    }
}
