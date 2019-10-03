<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Feed;

use Shopsys\FrameworkBundle\Model\Feed\FeedExport as BaseFeedExport;

class FeedExport extends BaseFeedExport
{
    public function sleep(): void
    {
        try {
            parent::sleep();
        } catch (\League\Flysystem\FileNotFoundException $e) {
        }
    }
}
