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
     * @var \App\Model\Advert\Advert[]
     */
    public array $adverts;

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

    /**
     * @var \App\Model\Product\Parameter\Parameter[]
     */
    public $filterParameters;

    /**
     * @var bool[]
     */
    public array $containsSaleProducts;

    /**
     * @var bool[]
     */
    public array $containsNewsProducts;

    public bool $unavailableProductsShown;

    public array $categoryBrands;

    /**
     * @var bool[]
     */
    public array $tipShown;

    /**
     * @var string[]|null[]
     */
    public array $tipName;

    /**
     * @var string[]|null[]
     */
    public array $tipText;

    /**
     * @var \App\Model\Product\Product[]|null[]
     */
    public array $tipProduct;

    public function __construct()
    {
        parent::__construct();

        $this->blogArticles = [];
        $this->leftBannerTexts = [];
        $this->rightBannerTexts = [];
        $this->adverts = [];
        $this->type = null;
        $this->filterParameters = [];
        $this->containsSaleProducts = [];
        $this->containsNewsProducts = [];
        $this->unavailableProductsShown = true;
        $this->categoryBrands = [];
        $this->tipShown = [];
        $this->tipName = [];
        $this->tipText = [];
        $this->tipProduct = [];
    }
}
