<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Sitemap;

use Shopsys\FrameworkBundle\Component\String\TransformString;
use Shopsys\FrameworkBundle\Model\Sitemap\SitemapDumper as BaseSitemapDumper;
use Symfony\Component\Finder\Finder;

class SitemapDumper extends BaseSitemapDumper
{
    /**
     * Moves sitemaps created in a temporary folder to their real location
     *
     * @param string $targetDir Directory to move created sitemaps to
     *
     * @throws \RuntimeException
     */
    protected function activate($targetDir)
    {
        $finder = new Finder();
        $sitemapFileFinder = $finder
            ->files()
            ->name(sprintf('%s*.xml', $this->sitemapFilePrefix))
            ->in($this->tmpFolder);

        foreach ($sitemapFileFinder->getIterator() as $file) {
            $filePath = $targetDir . '/' . $file->getBasename();

            if ($this->abstractFilesystem->has($filePath)) {
                $this->abstractFilesystem->update(
                    $targetDir . '/' . $file->getBasename(),
                    $file->getContents()
                );
            } else {
                $this->mountManager->move(
                    'local://' . TransformString::removeDriveLetterFromPath($file->getPathname()),
                    'main://' . $filePath
                );
            }
        }

        parent::cleanup();
    }
}
