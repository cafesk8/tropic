<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Blog\Article;

class BlogArticleFactory
{
    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleData $data
     * @return \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle
     */
    public function create(BlogArticleData $data): BlogArticle
    {
        return new BlogArticle($data);
    }
}
