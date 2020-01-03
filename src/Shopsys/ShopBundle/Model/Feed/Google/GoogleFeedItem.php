<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Feed\Google;

use Shopsys\ProductFeed\GoogleBundle\Model\FeedItem\GoogleFeedItem as BaseGoogleFeedItem;

class GoogleFeedItem extends BaseGoogleFeedItem
{
    /**
     * @var string
     */
    private $catnum;

    /**
     * @var string|null
     */
    private $categoryFullPath;

    /**
     * @return string
     */
    public function getCatnum(): string
    {
        return $this->catnum;
    }

    /**
     * @param string $catnum
     */
    public function setCatnum(string $catnum): void
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
     * @param string $categoryFullPath|null
     */
    public function setCategoryFullPath(?string $categoryFullPath): void
    {
        $this->categoryFullPath = $categoryFullPath;
    }
}
