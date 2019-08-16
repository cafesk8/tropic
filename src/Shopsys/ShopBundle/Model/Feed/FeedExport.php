<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Feed;

use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\String\TransformString;
use Shopsys\FrameworkBundle\Model\Feed\FeedExport as BaseFeedExport;
use Shopsys\FrameworkBundle\Model\Feed\FeedInterface;
use Shopsys\FrameworkBundle\Model\Feed\FeedRenderer;
use Symfony\Component\Filesystem\Filesystem;

class FeedExport extends BaseFeedExport
{
    /**
     * @var \League\Flysystem\FilesystemInterface
     */
    private $abstractFilesystem;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Feed\FeedInterface $feed
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param \Shopsys\FrameworkBundle\Model\Feed\FeedRenderer $feedRenderer
     * @param \League\Flysystem\FilesystemInterface $filesystem
     * @param \Symfony\Component\Filesystem\Filesystem $localFilesystem
     * @param \League\Flysystem\MountManager $mountManager
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param string $feedFilepath
     * @param string $feedLocalFilepath
     * @param int|null $lastSeekId
     * @param \League\Flysystem\FilesystemInterface $abstractFilesystem
     */
    public function __construct(FeedInterface $feed, DomainConfig $domainConfig, FeedRenderer $feedRenderer, FilesystemInterface $filesystem, Filesystem $localFilesystem, MountManager $mountManager, EntityManagerInterface $em, string $feedFilepath, string $feedLocalFilepath, ?int $lastSeekId, FilesystemInterface $abstractFilesystem)
    {
        parent::__construct($feed, $domainConfig, $feedRenderer, $filesystem, $localFilesystem, $mountManager, $em, $feedFilepath, $feedLocalFilepath, $lastSeekId);
        $this->abstractFilesystem = $abstractFilesystem;
    }

    protected function finishFile(): void
    {
        if ($this->abstractFilesystem->has($this->feedFilepath)) {
            $this->abstractFilesystem->delete($this->feedFilepath);
        }

        $this->mountManager->move('local://' . TransformString::removeDriveLetterFromPath($this->getTemporaryLocalFilepath()), 'main://' . $this->feedFilepath);

        $this->finished = true;
    }
}
