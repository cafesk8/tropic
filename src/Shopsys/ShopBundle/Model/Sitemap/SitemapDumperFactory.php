<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Sitemap;

use Shopsys\FrameworkBundle\Model\Sitemap\SitemapDumperFactory as BaseSitemapDumperFactory;

class SitemapDumperFactory extends BaseSitemapDumperFactory
{
    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Sitemap\SitemapDumper
     */
    public function createForDomain($domainId)
    {
        return new SitemapDumper(
            $this->eventDispatcher,
            $this->localFilesystem,
            $this->filesystem,
            $this->mountManager,
            $this->sitemapFilePrefixer->getSitemapFilePrefixForDomain($domainId),
            static::MAX_ITEMS_IN_FILE
        );
    }
}
