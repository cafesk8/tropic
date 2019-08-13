<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\GoogleApi;

use Google_Client;
use Google_Service_YouTube;
use Google_Service_YouTube_VideoListResponse;

class GoogleClient
{
    /**
     * @var \Google_Service_YouTube
     */
    private $youtubeService;

    /**
     * @param string $googleApiKey
     */
    public function __construct(string $googleApiKey)
    {
        $googleClient = new Google_Client();
        $googleClient->setApplicationName('Bushman Google API');
        $googleClient->setScopes([
            'https://www.googleapis.com/auth/youtube.readonly',
        ]);

        $googleClient->setDeveloperKey($googleApiKey);

        $this->youtubeService = new Google_Service_YouTube($googleClient);
    }

    /**
     * @param string $videoId
     * @return \Google_Service_YouTube_VideoListResponse
     */
    public function getVideoList(string $videoId): Google_Service_YouTube_VideoListResponse
    {
        $queryParams = [
            'id' => $videoId,
        ];

        return $this->youtubeService->videos->listVideos('snippet', $queryParams);
    }
}
