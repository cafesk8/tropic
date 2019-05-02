<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Blog;

use Shopsys\ShopBundle\Model\Blog\Article\BlogArticleVisibilityRepository;
use Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryVisibilityRepository;

class BlogVisibilityFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryVisibilityRepository
     */
    private $blogCategoryVisibilityRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleVisibilityRepository
     */
    private $blogArticleVisibilityRepository;

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryVisibilityRepository $blogCategoryVisibilityRepository
     * @param \Shopsys\ShopBundle\Model\Blog\Article\BlogArticleVisibilityRepository $blogArticleVisibilityRepository
     */
    public function __construct(
        BlogCategoryVisibilityRepository $blogCategoryVisibilityRepository,
        BlogArticleVisibilityRepository $blogArticleVisibilityRepository
    ) {
        $this->blogCategoryVisibilityRepository = $blogCategoryVisibilityRepository;
        $this->blogArticleVisibilityRepository = $blogArticleVisibilityRepository;
    }

    public function refreshBlogCategoriesVisibility(): void
    {
        $this->blogCategoryVisibilityRepository->refreshCategoriesVisibility();
    }

    public function refreshBlogArticlesVisibility(): void
    {
        $this->blogArticleVisibilityRepository->refreshArticlesVisibility();
    }
}
