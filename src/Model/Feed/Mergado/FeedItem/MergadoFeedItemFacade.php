<?php

declare(strict_types=1);

namespace App\Model\Feed\Mergado\FeedItem;

use App\Model\Product\ProductRepository;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;

class MergadoFeedItemFacade
{
    /**
     * @var \App\Model\Product\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    protected $pricingGroupSettingFacade;

    /**
     * @var \App\Model\Feed\Mergado\FeedItem\MergadoProductDataBatchLoader
     */
    protected $productDataBatchLoader;

    /**
     * @var \App\Model\Feed\Mergado\FeedItem\MergadoFeedItemFactory
     */
    private $feedItemFactory;

    /**
     * @param \App\Model\Product\ProductRepository $productRepository
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     * @param \App\Model\Feed\Mergado\FeedItem\MergadoFeedItemFactory $feedItemFactory
     * @param \App\Model\Feed\Mergado\FeedItem\MergadoProductDataBatchLoader $productDataBatchLoader
     */
    public function __construct(
        ProductRepository $productRepository,
        PricingGroupSettingFacade $pricingGroupSettingFacade,
        MergadoFeedItemFactory $feedItemFactory,
        MergadoProductDataBatchLoader $productDataBatchLoader
    ) {
        $this->productRepository = $productRepository;
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
        $this->productDataBatchLoader = $productDataBatchLoader;
        $this->feedItemFactory = $feedItemFactory;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param int|null $lastSeekId
     * @param int $maxResults
     * @return \Generator
     */
    public function getItems(
        DomainConfig $domainConfig,
        ?int $lastSeekId,
        int $maxResults
    ) {
        $pricingGroup = $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($domainConfig->getId());
        $products = $this->productRepository->getProductsForMergadoXmlFeed($domainConfig, $pricingGroup, $lastSeekId, $maxResults);
        $this->productDataBatchLoader->loadForProducts($products, $domainConfig);

        foreach ($products as $product) {
            yield $this->feedItemFactory->create($product, $domainConfig);
        }
    }
}
