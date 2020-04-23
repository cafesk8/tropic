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

    /**
     * @var \App\Model\Advert\Advert|null
     */
    public $advert;

    /**
     * @var int|null
     */
    public $pohodaId;

    /**
     * @var int|null
     */
    public $pohodaParentId;

    /**
     * @var \DateTime|null
     */
    public $updatedByPohodaAt;

    /**
     * @var int|null
     */
    public $pohodaPosition;

    /**
     * @var string|null
     */
    public $type;

    public function __construct()
    {
        parent::__construct();

        $this->blogArticles = [];
        $this->leftBannerTexts = [];
        $this->rightBannerTexts = [];
        $this->advert = null;
        $this->type = null;
    }
}
