<?php

declare(strict_types = 1);

namespace App\Model\Feed\Mergado;

use App\Model\Feed\Mergado\FeedItem\MergadoFeedItemFacade;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Feed\FeedInfoInterface;
use Shopsys\FrameworkBundle\Model\Feed\FeedInterface;

class MergadoFeed implements FeedInterface
{
    /**
     * @var \App\Model\Feed\Mergado\MergadoFeedInfo
     */
    protected $feedInfo;

    /**
     * @var \App\Model\Feed\Mergado\FeedItem\MergadoFeedItemFacade
     */
    protected $feedItemFacade;

    /**
     * @param \App\Model\Feed\Mergado\MergadoFeedInfo $feedInfo
     * @param \App\Model\Feed\Mergado\FeedItem\MergadoFeedItemFacade $feedItemFacade
     */
    public function __construct(MergadoFeedInfo $feedInfo, MergadoFeedItemFacade $feedItemFacade)
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
        return 'Admin/Content/MergadoFeed/feed.xml.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(DomainConfig $domainConfig, ?int $lastSeekId, int $maxResults): iterable
    {
        yield from $this->feedItemFacade->getItems($domainConfig, $lastSeekId, $maxResults);
    }
}
