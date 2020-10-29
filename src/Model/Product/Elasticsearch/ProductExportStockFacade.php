<?php

declare(strict_types=1);

namespace App\Model\Product\Elasticsearch;

use App\Component\Elasticsearch\IndexFacade;
use App\Model\Product\Search\FilterQueryFactory;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Elasticsearch\IndexDefinitionLoader;
use Shopsys\FrameworkBundle\Model\Product\Search\ProductElasticsearchRepository;

class ProductExportStockFacade
{
    private Domain $domain;

    private IndexDefinitionLoader $indexDefinitionLoader;

    private ProductIndex $productIndex;

    private IndexFacade $indexFacade;

    private FilterQueryFactory $filterQueryFactory;

    private ProductElasticsearchRepository $productElasticsearchRepository;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Component\Elasticsearch\IndexDefinitionLoader $indexDefinitionLoader
     * @param \App\Model\Product\Elasticsearch\ProductIndex $productIndex
     * @param \App\Component\Elasticsearch\IndexFacade $indexFacade
     * @param \App\Model\Product\Search\FilterQueryFactory $filterQueryFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\Search\ProductElasticsearchRepository $productElasticsearchRepository
     */
    public function __construct(
        Domain $domain,
        IndexDefinitionLoader $indexDefinitionLoader,
        ProductIndex $productIndex,
        IndexFacade $indexFacade,
        FilterQueryFactory $filterQueryFactory,
        ProductElasticsearchRepository $productElasticsearchRepository
    ) {
        $this->domain = $domain;
        $this->indexDefinitionLoader = $indexDefinitionLoader;
        $this->productIndex = $productIndex;
        $this->indexFacade = $indexFacade;
        $this->filterQueryFactory = $filterQueryFactory;
        $this->productElasticsearchRepository = $productElasticsearchRepository;
    }

    /**
     * @param int[] $productIds
     * @return int[]
     */
    public function exportStockInformation(array $productIds): array
    {
        $exportedCountByDomainId = [];
        foreach ($this->domain->getAllIds() as $domainId) {
            $indexDefinition = $this->indexDefinitionLoader->getIndexDefinition($this->productIndex::getName(), $domainId);
            $productIdsToExport = $this->getIdsAlreadyPresentInElastic($productIds, $indexDefinition->getIndexAlias());
            $exportedCountByDomainId[$domainId] = count($productIdsToExport);
            $this->indexFacade->exportIds($this->productIndex, $indexDefinition, $productIdsToExport, ProductExportRepository::SCOPE_STOCKS);
        }

        return $exportedCountByDomainId;
    }

    /**
     * We want to export only products that are already exported in Elasticsearch
     * @param array $ids
     * @param string $indexName
     * @return array
     */
    private function getIdsAlreadyPresentInElastic(array $ids, string $indexName): array
    {
        $filterQuery = $this->filterQueryFactory->create($indexName)
            ->filterIds(array_values($ids));
        $hits = $this->productElasticsearchRepository->getSortedProductsResultByFilterQuery($filterQuery)->getHits();

        return array_column($hits, 'id');
    }
}
