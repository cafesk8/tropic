<?php

declare(strict_types=1);

namespace App\Model\Category;

use Shopsys\FrameworkBundle\Model\Category\CategoryData as BaseCategoryData;

/**
 * @property \App\Model\Category\Category|null $parent
 */
class CategoryData extends BaseCategoryData
{
    /**
     * @var bool
     */
    public $displayedInHorizontalMenu = false;

    /**
     * @var bool
     */
    public $listable = true;

    /**
     * @var bool
     */
    public $preListingCategory = false;

    /**
     * @var \App\Model\Blog\Article\BlogArticle[]
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

    /**
     * @var string|null
     */
    public $mallCategoryId;

    /**
     * @var string[]|null[]
     */
    public $leftBannerTexts;

    /**
     * @var string[]|null[]
     */
    public $rightBannerTexts;

    public function __construct()
    {
        parent::__construct();

        $this->blogArticles = [];
        $this->displayedInFirstColumn = false;
        $this->legendaryCategory = false;
        $this->leftBannerTexts = [];
        $this->rightBannerTexts = [];
    }
}