<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Feed;

use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Feed\FeedExport as BaseFeedExport;
use Shopsys\FrameworkBundle\Model\Feed\FeedExportFactory as BaseFeedExportFactory;
use Shopsys\FrameworkBundle\Model\Feed\FeedInterface;
use Shopsys\FrameworkBundle\Model\Feed\FeedPathProvider;
use Shopsys\FrameworkBundle\Model\Feed\FeedRendererFactory;
use Symfony\Component\Filesystem\Filesystem;

class FeedExportFactory extends BaseFeedExportFactory
{
    /**
     * @var \League\Flysystem\FilesystemInterface
     */
    private $abstractFilesystem;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Feed\FeedRendererFactory $feedRendererFactory
     * @param \League\Flysystem\FilesystemInterface $filesystem
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Feed\FeedPathProvider $feedPathProvider
     * @param \Symfony\Component\Filesystem\Filesystem $localFilesystem
     * @param \League\Flysystem\MountManager $mountManager
     * @param \League\Flysystem\FilesystemInterface $abstractFilesystem
     */
    public function __construct(FeedRendererFactory $feedRendererFactory, FilesystemInterface $filesystem, EntityManagerInterface $em, FeedPathProvider $feedPathProvider, Filesystem $localFilesystem, MountManager $mountManager, FilesystemInterface $abstractFilesystem)
    {
        parent::__construct($feedRendererFactory, $filesystem, $em, $feedPathProvider, $localFilesystem, $mountManager);
        $this->abstractFilesystem = $abstractFilesystem;
    }

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
            $lastSeekId,
            $this->abstractFilesystem
        );
    }
}
