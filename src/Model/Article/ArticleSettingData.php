<?php

declare(strict_types=1);

namespace App\Model\Article;

class ArticleSettingData
{
    /**
     * @var \App\Model\Article\Article|null
     */
    public $loyaltyProgramArticle;

    /**
     * @var \App\Model\Article\Article|null
     */
    public $productSizeArticle;

    /**
     * @var \App\Model\Article\Article|null
     */
    public $ourValuesArticle;

    /**
     * @var \App\Model\Article\Article|null
     */
    public $ourStoryArticle;

    /**
     * @var \App\Model\Article\Article|null
     */
    public $firstArticleOnHeaderMenu;

    /**
     * @var \App\Model\Article\Article|null
     */
    public $secondArticleOnHeaderMenu;

    /**
     * @var \App\Model\Article\Article|null
     */
    public $thirdArticleOnHeaderMenu;
}
