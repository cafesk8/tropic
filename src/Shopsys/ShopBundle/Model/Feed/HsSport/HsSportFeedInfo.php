<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\Feed\HsSport;

use Shopsys\FrameworkBundle\Model\Feed\FeedInfoInterface;

class HsSportFeedInfo implements FeedInfoInterface
{
    /**
     * @return string
     */
    public function getLabel(): string
    {
        return 'HS-SPORT';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'hssport';
    }

    /**
     * @return string|null
     */
    public function getAdditionalInformation(): ?string
    {
        return null;
    }
}
