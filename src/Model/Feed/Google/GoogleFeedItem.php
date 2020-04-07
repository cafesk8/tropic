<?php

declare(strict_types=1);

namespace App\Model\Feed\Google;

use Shopsys\ProductFeed\GoogleBundle\Model\FeedItem\GoogleFeedItem as BaseGoogleFeedItem;

class GoogleFeedItem extends BaseGoogleFeedItem
{
    /**
     * @var string|null
     */
    private $catnum;

    /**
     * @var string|null
     */
    private $categoryFullPath;

    /**
     * @return string|null
     */
    public function getCatnum(): ?string
    {
        return $this->catnum;
    }

    /**
     * @param string|null $catnum
     */
    public function setCatnum(?string $catnum): void
    {
        $this->catnum = $catnum;
    }

    /**
     * @return string|null
     */
    public function getCategoryFullPath(): ?string
    {
        return $this->categoryFullPath;
    }

    /**
     * @param string|null $categoryFullPath
     */
    public function setCategoryFullPath(?string $categoryFullPath): void
    {
        $this->categoryFullPath = $categoryFullPath;
    }
}
