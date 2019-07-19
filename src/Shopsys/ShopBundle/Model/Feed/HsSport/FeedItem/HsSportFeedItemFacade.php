<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\Feed\HsSport\FeedItem;

use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;
use Shopsys\ShopBundle\Model\Product\ProductRepository;

class HsSportFeedItemFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\Feed\HsSport\FeedItem\HsSportFeedItemFactory
     */
    protected $feedItemFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    protected $pricingGroupSettingFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Feed\HsSport\FeedItem\HsSportProductDataBatchLoader
     */
    protected $productDataBatchLoader;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductRepository $productRepository
     * @param \Shopsys\ShopBundle\Model\Feed\HsSport\FeedItem\HsSportFeedItemFactory $feedItemFactory
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     * @param \Shopsys\ShopBundle\Model\Feed\HsSport\FeedItem\HsSportProductDataBatchLoader $productDataBatchLoader
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
     * @return \Shopsys\ShopBundle\Model\Feed\HsSport\FeedItem\HsSportFeedItem[]|iterable
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
