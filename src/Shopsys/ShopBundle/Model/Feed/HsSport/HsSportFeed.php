<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\Feed\HsSport;

use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Feed\FeedInfoInterface;
use Shopsys\FrameworkBundle\Model\Feed\FeedInterface;
use Shopsys\ShopBundle\Model\Feed\HsSport\FeedItem\HsSportFeedItemFacade;

class HsSportFeed implements FeedInterface
{
    /**
     * @var \Shopsys\ShopBundle\Model\Feed\HsSport\HsSportFeedInfo
     */
    protected $feedInfo;

    /**
     * @var \Shopsys\ShopBundle\Model\Feed\HsSport\FeedItem\HsSportFeedItemFacade
     */
    protected $feedItemFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Feed\HsSport\HsSportFeedInfo $feedInfo
     * @param \Shopsys\ShopBundle\Model\Feed\HsSport\FeedItem\HsSportFeedItemFacade $feedItemFacade
     */
    public function __construct(HsSportFeedInfo $feedInfo, HsSportFeedItemFacade $feedItemFacade)
    {
        $this->feedInfo = $feedInfo;
        $this->feedItemFacade = $feedItemFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo(): FeedInfoInterface
    {
        return $this->feedInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateFilepath(): string
    {
        return '@ShopsysShop/Admin/Content/HsSportFeed/feed.xml.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(DomainConfig $domainConfig, ?int $lastSeekId, int $maxResults): iterable
    {
        yield from $this->feedItemFacade->getItems($domainConfig, $lastSeekId, $maxResults);
    }
}
