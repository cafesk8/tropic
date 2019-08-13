<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\GoogleApi\Youtube;

class YoutubeView
{
    /**
     * @var string
     */
    private $videoId;

    /**
     * @var string
     */
    private $videoTitle;

    /**
     * @var string
     */
    private $thumbnailUrl;

    /**
     * @param string $videoId
     * @param string $thumbnailUrl
     * @param string $videoTitle
     */
    public function __construct(string $videoId, string $thumbnailUrl, string $videoTitle)
    {
        $this->videoId = $videoId;
        $this->thumbnailUrl = $thumbnailUrl;
        $this->videoTitle = $videoTitle;
    }

    /**
     * @return string
     */
    public function getVideoId(): string
    {
        return $this->videoId;
    }

    /**
     * @return string
     */
    public function getVideoTitle(): string
    {
        return $this->videoTitle;
    }

    /**
     * @return string
     */
    public function getThumbnailUrl(): string
    {
        return $this->thumbnailUrl;
    }
}
