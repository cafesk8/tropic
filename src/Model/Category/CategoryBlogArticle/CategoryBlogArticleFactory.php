<?php

declare(strict_types=1);

namespace App\Model\Category\CategoryBlogArticle;

use App\Model\Blog\Article\BlogArticle;
use App\Model\Category\Category;

class CategoryBlogArticleFactory
{
    /**
     * @param \App\Model\Category\Category $category
     * @param \App\Model\Blog\Article\BlogArticle $blogArticle
     * @param int $position
     * @return \App\Model\Category\CategoryBlogArticle\CategoryBlogArticle
     */
    public function create(Category $category, BlogArticle $blogArticle, int $position): CategoryBlogArticle
    {
        return new CategoryBlogArticle($category, $blogArticle, $position);
    }
}
