<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Category;

use Shopsys\FrameworkBundle\Model\Category\CategoryData as BaseCategoryData;

class CategoryData extends BaseCategoryData
{
    /**
     * @var bool
     */
    public $displayedInHorizontalMenu = false;

    /**
     * @var bool
     */
    public $preListingCategory = false;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Article\BlogArticle[]
     */
    public $blogArticles;

    /**
     * @var bool
     */
    public $displayedInFirstColumn;

    /**
     * @var bool
     */
    public $legendaryCategory;

    public function __construct()
    {
        parent::__construct();

        $this->blogArticles = [];
        $this->displayedInFirstColumn = false;
        $this->legendaryCategory = false;
    }
}
