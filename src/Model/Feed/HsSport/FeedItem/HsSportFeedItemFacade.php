<?php

declare(strict_types = 1);

namespace App\Model\Feed\HsSport\FeedItem;

use App\Model\Product\ProductRepository;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;

class HsSportFeedItemFacade
{
    /**
     * @var \App\Model\Product\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \App\Model\Feed\HsSport\FeedItem\HsSportFeedItemFactory
     */
    protected $feedItemFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    protected $pricingGroupSettingFacade;

    /**
     * @var \App\Model\Feed\HsSport\FeedItem\HsSportProductDataBatchLoader
     */
    protected $productDataBatchLoader;

    /**
     * @param \App\Model\Product\ProductRepository $productRepository
     * @param \App\Model\Feed\HsSport\FeedItem\HsSportFeedItemFactory $feedItemFactory
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     * @param \App\Model\Feed\HsSport\FeedItem\HsSportProductDataBatchLoader $productDataBatchLoader
     */
    public function __construct(
        ProductRepository $productRepository,
        HsSportFeedItemFactory $feedItemFactory,
        PricingGroupSettingFacade $pricingGroupSettingFacade,
        HsSportProductDataBatchLoader $productDataBatchLoader
    ) {
        $this->productRepository = $productRepository;
        $this->feedItemFactory = $feedItemFactory;
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
        $this->productDataBatchLoader = $productDataBatchLoader;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param int|null $lastSeekId
     * @param int $maxResults
     * @return \App\Model\Feed\HsSport\FeedItem\HsSportFeedItem[]|iterable
     */
    public function getItems(DomainConfig $domainConfig, ?int $lastSeekId, int $maxResults): iterable
    {
        $pricingGroup = $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($domainConfig->getId());
        $products = $this->productRepository->getProductsForHsSportXmlFeed($domainConfig, $pricingGroup, $lastSeekId, $maxResults);
        $this->productDataBatchLoader->loadForProducts($products, $domainConfig);

        foreach ($products as $product) {
            yield $this->feedItemFactory->create($product, $domainConfig);
        }
    }
}
