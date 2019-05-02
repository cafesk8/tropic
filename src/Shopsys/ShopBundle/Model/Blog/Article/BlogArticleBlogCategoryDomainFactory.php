<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Blog\Article;

use Shopsys\ShopBundle\Model\Blog\Category\BlogCategory;

class BlogArticleBlogCategoryDomainFactory
{
    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle $blogArticle
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory $blogCategory
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleBlogCategoryDomain
     */
    public function create(
        BlogArticle $blogArticle,
        BlogCategory $blogCategory,
        int $domainId
    ): BlogArticleBlogCategoryDomain {
        return new BlogArticleBlogCategoryDomain($blogArticle, $blogCategory, $domainId);
    }
}
