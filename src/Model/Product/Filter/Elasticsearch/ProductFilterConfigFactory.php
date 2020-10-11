<?php

declare(strict_types=1);

namespace App\Model\Product\Filter\Elasticsearch;

use App\Model\Product\Brand\BrandFacade;
use App\Model\Product\Flag\FlagFacade;
use App\Model\Product\Parameter\ParameterFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Category\Category as BaseCategory;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Product\Filter\PriceRange;
use Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig;

class ProductFilterConfigFactory
{
    private Domain $domain;

    private CurrentCustomerUser $currentCustomerUser;

    private ProductFilterElasticFacade $productFilterElasticFacade;

    private FlagFacade $flagFacade;

    private BrandFacade $brandFacade;

    private ParameterFacade $parameterFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \App\Model\Product\Filter\Elasticsearch\ProductFilterElasticFacade $productFilterElasticFacade
     * @param \App\Model\Product\Flag\FlagFacade $flagFacade
     * @param \App\Model\Product\Brand\BrandFacade $brandFacade
     * @param \App\Model\Product\Parameter\ParameterFacade $parameterFacade
     */
    public function __construct(
        Domain $domain,
        CurrentCustomerUser $currentCustomerUser,
        ProductFilterElasticFacade $productFilterElasticFacade,
        FlagFacade $flagFacade,
        BrandFacade $brandFacade,
        ParameterFacade $parameterFacade
    ) {
        $this->domain = $domain;
        $this->currentCustomerUser = $currentCustomerUser;
        $this->productFilterElasticFacade = $productFilterElasticFacade;
        $this->flagFacade = $flagFacade;
        $this->brandFacade = $brandFacade;
        $this->parameterFacade = $parameterFacade;
    }

    /**
     * @param \App\Model\Category\Category $category
     * @param array $onlyFlags
     * @param bool $showUnavailableProducts
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig
     */
    public function createForCategory(BaseCategory $category, array $onlyFlags = [], bool $showUnavailableProducts = false): ProductFilterConfig
    {
        $elasticFilterData = $this->productFilterElasticFacade->getProductFilterDataInCategory($category->getId(), $this->currentCustomerUser->getPricingGroup(), $showUnavailableProducts);

        if ($onlyFlags || ($category->isSaleType() || $category->isNewsType())) {
            return new ProductFilterConfig(
                [],
                [],
                $this->aggregateBrandsData($elasticFilterData, $category),
                $this->aggregatePriceRangeData($elasticFilterData)
            );
        }

        return new ProductFilterConfig(
            $this->aggregateParametersData($elasticFilterData),
            $this->aggregateFlagsData($elasticFilterData),
            $this->aggregateBrandsData($elasticFilterData, $category),
            $this->aggregatePriceRangeData($elasticFilterData)
        );
    }

    /**
     * @param string|null $searchText
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterConfig
     */
    public function createForSearch(?string $searchText = null): ProductFilterConfig
    {
        $elasticFilterData = $this->productFilterElasticFacade->getProductFilterDataForSearch($searchText, $this->currentCustomerUser->getPricingGroup());

        return new ProductFilterConfig(
            [],
            $this->aggregateFlagsData($elasticFilterData),
            $this->aggregateBrandsData($elasticFilterData),
            $this->aggregatePriceRangeData($elasticFilterData)
        );
    }

    /**
     * @param array $elasticFilterData
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\PriceRange
     */
    private function aggregatePriceRangeData(array $elasticFilterData): PriceRange
    {
        $pricesData = $elasticFilterData['aggregations']['prices']['filter_pricing_group'];

        $minPrice = Money::create((string)($pricesData['min_price']['value'] ?? 0));
        $maxPrice = Money::create((string)($pricesData['max_price']['value'] ?? 0));

        return new PriceRange($minPrice, $maxPrice);
    }

    /**
     * @param array $elasticFilterData
     * @return \App\Model\Product\Flag\Flag[]
     */
    private function aggregateFlagsData(array $elasticFilterData): array
    {
        $flagsData = $elasticFilterData['aggregations']['flags']['buckets'];

        $flagsIds = array_map(function(array $data) {
            return $data['key'];
        }, $flagsData);

        if (count($flagsIds) === 0) {
            return [];
        }

        return $this->flagFacade->getFlagsForFilterByIds($flagsIds, $this->domain->getLocale());
    }

    /**
     * @param array $elasticFilterData
     * @param \App\Model\Category\Category|null $category
     * @return \App\Model\Product\Brand\Brand[]
     */
    private function aggregateBrandsData(array $elasticFilterData, ?BaseCategory $category = null): array
    {
        $brandsData = $elasticFilterData['aggregations']['brands']['buckets'];

        $brandsIds = array_map(function(array $data) {
            return $data['key'];
        }, $brandsData);

        if (count($brandsIds) === 0) {
            return [];
        }

        return $this->brandFacade->getBrandsForFilterByIds($brandsIds, $this->domain->getLocale(), $category);
    }

    /**
     * @param array $elasticFilterData
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\ParameterFilterChoice[]
     */
    private function aggregateParametersData(array $elasticFilterData): array
    {
        $parametersData = $elasticFilterData['aggregations']['parameters']['by_parameters']['buckets'];

        $parameterValueIdsIndexedByParameterId = [];

        foreach ($parametersData as $parameter) {
            $parameterValueIdsIndexedByParameterId[$parameter['key']] = array_map(function($parameter) {
                return $parameter['key'];
            }, $parameter['by_value']['buckets']);
        }

        if (count($parameterValueIdsIndexedByParameterId) === 0) {
            return [];
        }

        return $this->parameterFacade->getParameterFilterChoicesByIds($parameterValueIdsIndexedByParameterId, $this->domain->getLocale());
    }
}
