<?php

declare(strict_types=1);

namespace App\Model\Feed;

use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Feed\FeedExport;
use Shopsys\FrameworkBundle\Model\Feed\FeedFacade as BaseFeedFacade;

/**
 * @property \App\Model\Feed\FeedExportFactory $feedExportFactory
 * @method __construct(\Shopsys\FrameworkBundle\Model\Feed\FeedRegistry $feedRegistry, \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade $productVisibilityFacade, \App\Model\Feed\FeedExportFactory $feedExportFactory, \Shopsys\FrameworkBundle\Model\Feed\FeedPathProvider $feedPathProvider, \League\Flysystem\FilesystemInterface $filesystem)
 */
class FeedFacade extends BaseFeedFacade
{
    /**
     * Doesn't recalculate product visibilities because they are calculated by a different cron every 5 minutes, so it isn't necessary
     *
     * @param string $feedName
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param int|null $lastSeekId
     * @return \Shopsys\FrameworkBundle\Model\Feed\FeedExport
     */
    public function createFeedExport(string $feedName, DomainConfig $domainConfig, ?int $lastSeekId = null): FeedExport
    {
        $feed = $this->feedRegistry->getFeedByName($feedName);
        $lastSeekId = $lastSeekId !== null ? (string)$lastSeekId : $lastSeekId;

        return $this->feedExportFactory->create($feed, $domainConfig, $lastSeekId);
    }
}
