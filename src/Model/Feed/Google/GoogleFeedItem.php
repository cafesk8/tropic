<?php

declare(strict_types=1);

namespace App\Model\Feed\Google;

use Shopsys\ProductFeed\GoogleBundle\Model\FeedItem\GoogleFeedItem as BaseGoogleFeedItem;

class GoogleFeedItem extends BaseGoogleFeedItem
{
    /**
     * @var int|null
     */
    private $groupId;

    /**
     * @var string|null
     */
    private $categoryFullPath;

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

    /**
     * @return int|null
     */
    public function getGroupId(): ?int
    {
        return $this->groupId;
    }

    /**
     * @param int|null $groupId
     */
    public function setGroupId(?int $groupId): void
    {
        $this->groupId = $groupId;
    }
}
