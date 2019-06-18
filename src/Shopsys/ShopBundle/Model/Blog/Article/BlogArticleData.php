<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Blog\Article;

use Shopsys\FrameworkBundle\Component\FileUpload\ImageUploadData;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\UrlListData;

class BlogArticleData
{
    /**
     * @var string[]|null[]
     */
    public $names;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public $blogCategoriesByDomainId;

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
     * @var string[]|null[]
     */
    public $descriptions;

    /**
     * @var \Shopsys\ShopBundle\Model\Blog\Category\BlogCategory[]
     */
    public $categories;

    /**
     * @var bool[]
     */
    public $enabled;

    /**
     * @var bool
     */
    public $hidden;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\UrlListData
     */
    public $urls;

    /**
     * @var \Shopsys\FrameworkBundle\Component\FileUpload\ImageUploadData
     */
    public $image;

    /**
     * @var bool
     */
    public $visibleOnHomepage;

    /**
     * @var \DateTime|null
     */
    public $publishDate;

    /**
     * @var string[]|null[]
     */
    public $perexes;

    public function __construct()
    {
        $this->names = [];
        $this->blogCategoriesByDomainId = [];
        $this->seoTitles = [];
        $this->seoMetaDescriptions = [];
        $this->seoH1s = [];
        $this->descriptions = [];
        $this->categories = [];
        $this->enabled = [];
        $this->hidden = false;
        $this->urls = new UrlListData();
        $this->image = new ImageUploadData();
        $this->visibleOnHomepage = true;
        $this->perexes = [];
    }
}
