<?php

declare(strict_types=1);

namespace App\Model\Product\Search;

use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductIndex;
use Shopsys\FrameworkBundle\Model\Product\Search\ProductElasticsearchRepository as BaseProductElasticsearchRepository;

/**
 * @property \App\Model\Product\Search\ProductElasticsearchConverter $productElasticsearchConverter
 * @property \App\Model\Product\Search\FilterQueryFactory $filterQueryFactory
 * @method __construct(\Elasticsearch\Client $client, \App\Model\Product\Search\ProductElasticsearchConverter $productElasticsearchConverter, \App\Model\Product\Search\FilterQueryFactory $filterQueryFactory, \Shopsys\FrameworkBundle\Component\Elasticsearch\IndexDefinitionLoader $indexDefinitionLoader)
 * @method \Shopsys\FrameworkBundle\Model\Product\Search\ProductIdsResult getSortedProductIdsByFilterQuery(\App\Model\Product\Search\FilterQuery $filterQuery)
 * @method \Shopsys\FrameworkBundle\Model\Product\Search\ProductsResult getSortedProductsResultByFilterQuery(\App\Model\Product\Search\FilterQuery $filterQuery)
 */
class ProductElasticsearchRepository extends BaseProductElasticsearchRepository
{
    /**
     * @inheritDoc
     */
    protected function extractTotalCount(array $result): int
    {
        return (int)$result['hits']['total']['value'];
    }

    /**
     * @param int $domainId
     * @param int[] $keepIds
     */
    public function deleteNotPresent(int $domainId, array $keepIds): void
    {
        $this->client->deleteByQuery([
            'index' => $this->indexDefinitionLoader->getIndexDefinition(ProductIndex::getName(), $domainId),
            'body' => [
                'query' => [
                    'bool' => [
                        'must_not' => [
                            'ids' => [
                                'values' => $keepIds,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param int $domainId
     * @param int[] $deleteIds
     */
    public function delete(int $domainId, array $deleteIds): void
    {
        $this->client->deleteByQuery([
            'index' => $this->indexDefinitionLoader->getIndexDefinition(ProductIndex::getName(), $domainId),
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [
                            'ids' => [
                                'values' => array_values($deleteIds),
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
