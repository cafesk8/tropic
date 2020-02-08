<?php

declare(strict_types=1);

namespace App\Model\Product;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Elasticsearch\ElasticsearchStructureManager;
use Shopsys\FrameworkBundle\Component\Paginator\PaginationResult;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryRepository;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterData as BaseProductFilterData;
use Shopsys\FrameworkBundle\Model\Product\Listing\ProductListOrderingConfig;
use Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainElasticFacade as BaseProductOnCurrentDomainElasticFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository;
use Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery;
use Shopsys\FrameworkBundle\Model\Product\Search\FilterQueryFactory;
use Shopsys\FrameworkBundle\Model\Product\Search\ProductElasticsearchRepository;
use Shopsys\FrameworkBundle\Model\Product\Search\ProductFilterCountDataElasticsearchRepository;
use Shopsys\FrameworkBundle\Model\Product\Search\ProductFilterDataToQueryTransformer;
use App\Model\Product\Filter\ProductFilterData;
use App\Model\Product\Parameter\Parameter;
use App\Model\Product\Parameter\ParameterFacade;

/**
 * @property \App\Model\Product\Search\FilterQueryFactory $filterQueryFactory
 * @method \App\Model\Product\Product getVisibleProductById(int $productId)
 * @method \App\Model\Product\Product[] getAccessoriesForProduct(\App\Model\Product\Product $product)
 * @method \App\Model\Product\Product[] getVariantsForProduct(\App\Model\Product\Product $product)
 * @method \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult getPaginatedProductsInCategory(\App\Model\Product\Filter\ProductFilterData $productFilterData, string $orderingModeId, int $page, int $limit, int $categoryId)
 * @method \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult getPaginatedProductsForSearch(string|null $searchText, \App\Model\Product\Filter\ProductFilterData $productFilterData, string $orderingModeId, int $page, int $limit)
 * @method \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterCountData getProductFilterCountDataInCategory(int $categoryId, \App\Model\Product\Filter\ProductFilterConfig $productFilterConfig, \App\Model\Product\Filter\ProductFilterData $productFilterData)
 * @method \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterCountData getProductFilterCountDataForSearch(string|null $searchText, \App\Model\Product\Filter\ProductFilterConfig $productFilterConfig, \App\Model\Product\Filter\ProductFilterData $productFilterData)
 */
class ProductOnCurrentDomainElasticFacade extends BaseProductOnCurrentDomainElasticFacade
{
    /**
     * @var \App\Model\Product\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \App\Model\Product\Search\ProductFilterDataToQueryTransformer
     */
    protected $productFilterDataToQueryTransformer;

    /**
     * @var \App\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * ProductOnCurrentDomainElasticFacade constructor.
     * @param \App\Model\Product\ProductRepository $productRepository
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryRepository $productAccessoryRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\Search\ProductElasticsearchRepository $productElasticsearchRepository
     * @param \App\Model\Product\Search\ProductFilterCountDataElasticsearchRepository $productFilterCountDataElasticsearchRepository
     * @param \Shopsys\FrameworkBundle\Component\Elasticsearch\ElasticsearchStructureManager $elasticsearchStructureManager
     * @param \App\Model\Product\Search\ProductFilterDataToQueryTransformer $productFilterDataToQueryTransformer
     * @param \App\Model\Product\Search\FilterQueryFactory $filterQueryFactory
     * @param \App\Model\Product\Parameter\ParameterFacade $parameterFacade
     */
    public function __construct(
        ProductRepository $productRepository,
        Domain $domain,
        CurrentCustomerUser $currentCustomerUser,
        ProductAccessoryRepository $productAccessoryRepository,
        ProductElasticsearchRepository $productElasticsearchRepository,
        ProductFilterCountDataElasticsearchRepository $productFilterCountDataElasticsearchRepository,
        ElasticsearchStructureManager $elasticsearchStructureManager,
        ProductFilterDataToQueryTransformer $productFilterDataToQueryTransformer,
        FilterQueryFactory $filterQueryFactory,
        ParameterFacade $parameterFacade
    ) {
        parent::__construct($productRepository, $domain, $currentCustomerUser, $productAccessoryRepository, $productElasticsearchRepository, $productFilterCountDataElasticsearchRepository, $elasticsearchStructureManager, $productFilterDataToQueryTransformer, $filterQueryFactory);
        $this->parameterFacade = $parameterFacade;
    }

    /**
     * @param \App\Model\Product\Product[] $products
     * @return \App\Model\Product\Product[]
     */
    public function getVariantsForProducts(array $products): array
    {
        return $this->productRepository->getAllSellableVariantsForMainVariants(
            $products,
            $this->domain->getId(),
            $this->currentCustomerUser->getPricingGroup()
        );
    }

    /**
     * @param \App\Model\Product\Product[] $products
     * @return \App\Model\Product\Product[][]
     */
    public function getVariantsIndexedByMainVariantId(array $products): array
    {
        return $this->productRepository->getVariantsIndexedByMainVariantId(
            $products,
            $this->domain->getId(),
            $this->currentCustomerUser->getPricingGroup()
        );
    }

    /**
     * @param \App\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @return \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery
     */
    protected function createFilterQueryWithProductFilterData(BaseProductFilterData $productFilterData, $orderingModeId, $page, $limit): FilterQuery
    {
        $filterQuery = $this->filterQueryFactory->create($this->getIndexName())
            ->filterOnlySellable()
            ->filterOnlyVisible($this->currentCustomerUser->getPricingGroup())
            ->setPage($page)
            ->setLimit($limit)
            ->applyOrdering($orderingModeId, $this->currentCustomerUser->getPricingGroup());

        $distinguishingParameters = [
            Parameter::TYPE_COLOR => $this->parameterFacade->getColorParameter(),
            Parameter::TYPE_SIZE => $this->parameterFacade->getSizeParameter(),
        ];

        $filterQuery = $this->productFilterDataToQueryTransformer->addBrandsToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addFlagsToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addParametersToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addDistinguishingParametersToQuery($productFilterData, $filterQuery, $distinguishingParameters);
        $filterQuery = $this->productFilterDataToQueryTransformer->addStockToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addPricesToQuery($productFilterData, $filterQuery, $this->currentCustomerUser->getPricingGroup());

        return $filterQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedProductsForBrand($orderingModeId, $page, $limit, $brandId): PaginationResult
    {
        $emptyProductFilterData = new ProductFilterData();

        $filterQuery = $this->createListableProductsForBrandFilterQuery($emptyProductFilterData, $orderingModeId, $page, $limit, $brandId);

        $productIds = $this->productElasticsearchRepository->getSortedProductIdsByFilterQuery($filterQuery);

        $listableProductsByIds = $this->productRepository->getListableByIds($this->domain->getId(), $this->currentCustomerUser->getPricingGroup(), $productIds->getIds());

        return new PaginationResult($page, $limit, $productIds->getTotal(), $listableProductsByIds);
    }

    /**
     * {@inheritdoc}
     * Copy pasted from parent to create the proper instance of ProductFilterData - the class is overridden in this project
     */
    public function getSearchAutocompleteProducts($searchText, $limit): PaginationResult
    {
        $emptyProductFilterData = new ProductFilterData();
        $page = 1;

        $filterQuery = $this->createListableProductsForSearchTextFilterQuery($emptyProductFilterData, ProductListOrderingConfig::ORDER_BY_RELEVANCE, $page, $limit, $searchText);

        $productIds = $this->productElasticsearchRepository->getSortedProductIdsByFilterQuery($filterQuery);

        $listableProductsByIds = $this->productRepository->getListableByIds($this->domain->getId(), $this->currentCustomerUser->getPricingGroup(), $productIds->getIds());

        return new PaginationResult($page, $limit, $productIds->getTotal(), $listableProductsByIds);
    }

    /**
     * @param int[] $ids
     * @return array
     */
    public function getHitsForIds(array $ids): array
    {
        $filterQuery = $this->filterQueryFactory->create($this->getIndexName())->filterIds(array_values($ids));

        return $this->productElasticsearchRepository->getSortedProductsResultByFilterQuery($filterQuery)->getHits();
    }
}
