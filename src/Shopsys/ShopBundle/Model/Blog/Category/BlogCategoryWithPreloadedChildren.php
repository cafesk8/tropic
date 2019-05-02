<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Blog\Category;

class BlogCategoryWithPreloadedChildren
{
    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory
     */
    private $blogCategory;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryWithPreloadedChildren[]
     */
    private $children;

    /**
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory $blogCategory
     * @param \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryWithPreloadedChildren[] $children
     */
    public function __construct(
        BlogCategory $blogCategory,
        array $children
    ) {
        $this->blogCategory = $blogCategory;
        $this->children = $children;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory
     */
    public function getBlogCategory(): BlogCategory
    {
        return $this->blogCategory;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Blog\Category\BlogCategoryWithPreloadedChildren[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
