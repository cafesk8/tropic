<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Feed;

use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Feed\FeedExport as BaseFeedExport;
use Shopsys\FrameworkBundle\Model\Feed\FeedExportFactory as BaseFeedExportFactory;
use Shopsys\FrameworkBundle\Model\Feed\FeedInterface;

class FeedExportFactory extends BaseFeedExportFactory
{
    /**
     * @param \Shopsys\FrameworkBundle\Model\Feed\FeedInterface $feed
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param string|null $lastSeekId
     * @return \Shopsys\FrameworkBundle\Model\Feed\FeedExport
     */
    public function create(FeedInterface $feed, DomainConfig $domainConfig, ?string $lastSeekId = null): BaseFeedExport
    {
        $feedRenderer = $this->feedRendererFactory->create($feed);
        $feedFilepath = $this->feedPathProvider->getFeedFilepath($feed->getInfo(), $domainConfig);
        $feedLocalFilepath = $this->feedPathProvider->getFeedLocalFilepath($feed->getInfo(), $domainConfig);

        $lastSeekId = $lastSeekId === null ? null : (int)$lastSeekId;

        return new FeedExport(
            $feed,
            $domainConfig,
            $feedRenderer,
            $this->filesystem,
            $this->localFilesystem,
            $this->mountManager,
            $this->em,
            $feedFilepath,
            $feedLocalFilepath,
            $lastSeekId
        );
    }
}
