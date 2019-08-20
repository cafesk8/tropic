<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Blog\Category;

use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\UrlListData;

class BlogCategoryData
{
    /**
     * @var string[]|null[]
     */
    public $names;

    /**
     * @var string[]|null[]
     */
    public $seoTitles;

    /**
     * @var string[]|null[]
     */
    public $seoMetaDescriptions;

    /**
     * @var string[]|null[]
     */
    public $seoH1s;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory|null
     */
    public $parent;

    /**
     * @var bool[]
     */
    public $enabled;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\UrlListData
     */
    public $urls;

    public function __construct()
    {
        $this->names = [];
        $this->seoTitles = [];
        $this->seoMetaDescriptions = [];
        $this->seoH1s = [];
        $this->parent = null;
        $this->enabled = [];
        $this->urls = new UrlListData();
    }
}
