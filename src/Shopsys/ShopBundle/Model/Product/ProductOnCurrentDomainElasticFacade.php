<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Elasticsearch\ElasticsearchStructureManager;
use Shopsys\FrameworkBundle\Component\Paginator\PaginationResult;
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
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
use Shopsys\ShopBundle\Model\Product\Filter\ProductFilterData;
use Shopsys\ShopBundle\Model\Product\Parameter\Parameter;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade;

class ProductOnCurrentDomainElasticFacade extends BaseProductOnCurrentDomainElasticFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Search\ProductFilterDataToQueryTransformer
     */
    protected $productFilterDataToQueryTransformer;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * ProductOnCurrentDomainElasticFacade constructor.
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductRepository $productRepository
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     * @param \Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryRepository $productAccessoryRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\Search\ProductElasticsearchRepository $productElasticsearchRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\Search\ProductFilterCountDataElasticsearchRepository $productFilterCountDataElasticsearchRepository
     * @param \Shopsys\FrameworkBundle\Component\Elasticsearch\ElasticsearchStructureManager $elasticsearchStructureManager
     * @param \Shopsys\ShopBundle\Model\Product\Search\ProductFilterDataToQueryTransformer $productFilterDataToQueryTransformer
     * @param \Shopsys\FrameworkBundle\Model\Product\Search\FilterQueryFactory $filterQueryFactory
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade $parameterFacade
     */
    public function __construct(
        ProductRepository $productRepository,
        Domain $domain,
        CurrentCustomer $currentCustomer,
        ProductAccessoryRepository $productAccessoryRepository,
        ProductElasticsearchRepository $productElasticsearchRepository,
        ProductFilterCountDataElasticsearchRepository $productFilterCountDataElasticsearchRepository,
        ElasticsearchStructureManager $elasticsearchStructureManager,
        ProductFilterDataToQueryTransformer $productFilterDataToQueryTransformer,
        FilterQueryFactory $filterQueryFactory,
        ParameterFacade $parameterFacade
    ) {
        parent::__construct($productRepository, $domain, $currentCustomer, $productAccessoryRepository, $productElasticsearchRepository, $productFilterCountDataElasticsearchRepository, $elasticsearchStructureManager, $productFilterDataToQueryTransformer, $filterQueryFactory);
        $this->parameterFacade = $parameterFacade;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product[] $products
     * @return \Shopsys\FrameworkBundle\Model\Product\Product[]
     */
    public function getVariantsForProducts(array $products): array
    {
        return $this->productRepository->getAllSellableVariantsForMainVariants(
            $products,
            $this->domain->getId(),
            $this->currentCustomer->getPricingGroup()
        );
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $products
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getVariantsIndexedByMainVariantId(array $products): array
    {
        return $this->productRepository->getVariantsIndexedByMainVariantId(
            $products,
            $this->domain->getId(),
            $this->currentCustomer->getPricingGroup()
        );
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Filter\ProductFilterData $productFilterData
     * @param string $orderingModeId
     * @param int $page
     * @param int $limit
     * @return \Shopsys\FrameworkBundle\Model\Product\Search\FilterQuery
     */
    protected function createFilterQueryWithProductFilterData(BaseProductFilterData $productFilterData, $orderingModeId, $page, $limit): FilterQuery
    {
        $filterQuery = $this->filterQueryFactory->create($this->getIndexName())
            ->filterOnlySellable()
            ->filterOnlyVisible($this->currentCustomer->getPricingGroup())
            ->setPage($page)
            ->setLimit($limit)
            ->applyOrdering($orderingModeId, $this->currentCustomer->getPricingGroup());

        $distinguishingParameters = [
            Parameter::TYPE_COLOR => $this->parameterFacade->getColorParameter(),
            Parameter::TYPE_SIZE => $this->parameterFacade->getSizeParameter(),
        ];

        $filterQuery = $this->productFilterDataToQueryTransformer->addBrandsToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addFlagsToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addParametersToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addDistinguishingParametersToQuery($productFilterData, $filterQuery, $distinguishingParameters);
        $filterQuery = $this->productFilterDataToQueryTransformer->addStockToQuery($productFilterData, $filterQuery);
        $filterQuery = $this->productFilterDataToQueryTransformer->addPricesToQuery($productFilterData, $filterQuery, $this->currentCustomer->getPricingGroup());

        return $filterQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedProductsForBrand($orderingModeId, $page, $limit, $brandId): PaginationResult
    {
        $emptyProductFilterData = new ProductFilterData();

        $filterQuery = $this->createProductsForBrandFilterQuery($emptyProductFilterData, $orderingModeId, $page, $limit, $brandId);

        $productIds = $this->productElasticsearchRepository->getSortedProductIdsByFilterQuery($filterQuery);

        $listableProductsByIds = $this->productRepository->getListableByIds($this->domain->getId(), $this->currentCustomer->getPricingGroup(), $productIds->getIds());

        return new PaginationResult($page, $limit, $productIds->getTotal(), $listableProductsByIds);
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchAutocompleteProducts($searchText, $limit): PaginationResult
    {
        $emptyProductFilterData = new ProductFilterData();
        $page = 1;

        return $this->getPaginatedProductsForSearch($searchText, $emptyProductFilterData, ProductListOrderingConfig::ORDER_BY_RELEVANCE, $page, $limit);
    }
}
