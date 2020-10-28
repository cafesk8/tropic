<?php

declare(strict_types=1);

namespace App\Model\Product\Filter\Elasticsearch;

use App\Model\Category\Category;
use App\Model\Pricing\Group\PricingGroup;
use App\Model\Product\Product;
use App\Model\Product\Search\FilterQueryFactory;
use Elasticsearch\Client;
use Shopsys\Cdn\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Elasticsearch\IndexDefinitionLoader;
use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductIndex;

class ProductFilterElasticFacade
{
    private FilterQueryFactory $filterQueryFactory;

    private IndexDefinitionLoader $indexDefinitionLoader;

    private Domain $domain;

    private Client $client;

    /**
     * @param \App\Model\Product\Search\FilterQueryFactory $filterQueryFactory
     * @param \Shopsys\FrameworkBundle\Component\Elasticsearch\IndexDefinitionLoader $indexDefinitionLoader
     * @param \Shopsys\Cdn\Component\Domain\Domain $domain
     * @param \Elasticsearch\Client $client
     */
    public function __construct(
        FilterQueryFactory $filterQueryFactory,
        IndexDefinitionLoader $indexDefinitionLoader,
        Domain $domain,
        Client $client
    ) {
        $this->filterQueryFactory = $filterQueryFactory;
        $this->indexDefinitionLoader = $indexDefinitionLoader;
        $this->domain = $domain;
        $this->client = $client;
    }

    /**
     * @return string
     */
    private function getIndexName(): string
    {
        return $this->indexDefinitionLoader->getIndexDefinition(
            ProductIndex::getName(),
            $this->domain->getId()
        )->getIndexAlias();
    }

    /**
     * @param int $categoryId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param bool $showUnavailableProducts
     * @param string|null $categoryType
     * @return array
     */
    public function getProductFilterDataInCategory(
        int $categoryId,
        PricingGroup $pricingGroup,
        bool $showUnavailableProducts = false,
        ?string $categoryType = null
    ): array {
        $baseFilterQuery = $this->filterQueryFactory->create($this->getIndexName())
            ->filterOnlyVisible($pricingGroup)
            ->filterByCategory([$categoryId])
            ->excludeVariants();
        if (!$showUnavailableProducts) {
            $baseFilterQuery = $baseFilterQuery->filterOnlySellable();
        }
        if ($categoryType === Category::SALE_TYPE) {
            $baseFilterQuery = $baseFilterQuery->filterOnlyInSale();
        }
        if ($categoryType === Category::NEWS_TYPE) {
            $baseFilterQuery = $baseFilterQuery->filterOnlyInNews();
        }

        return $this->client->search($baseFilterQuery->getFilterQuery($pricingGroup->getId()));
    }

    /**
     * @param string|null $searchText
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return array
     */
    public function getProductFilterDataForSearch(?string $searchText, PricingGroup $pricingGroup): array
    {
        $searchText = $searchText ?? '';

        $baseFilterQuery = $this->filterQueryFactory->create($this->getIndexName())
            ->filterOnlyVisible($pricingGroup)
            ->filterByPohodaProductType(Product::POHODA_PRODUCT_TYPE_ID_SINGLE_PRODUCT)
            ->search($searchText)
            ->excludeVariants();

        $filterQuery = $baseFilterQuery->getFilterQuery($pricingGroup->getId());

        // Remove parameters from filter on search page
        unset($filterQuery['body']['aggs']['parameters']);

        return $this->client->search($filterQuery);
    }
}
