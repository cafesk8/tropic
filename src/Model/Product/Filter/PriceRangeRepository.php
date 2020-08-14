<?php

declare(strict_types=1);

namespace App\Model\Product\Filter;

use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Product\StoreStock\ProductStoreStock;
use App\Model\Store\StoreFacade;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Component\Doctrine\QueryBuilderExtender;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Filter\PriceRange;
use Shopsys\FrameworkBundle\Model\Product\Filter\PriceRangeRepository as BasePriceRangeRepository;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductCalculatedPrice;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository;

/**
 * @property \App\Model\Product\ProductRepository $productRepository
 */
class PriceRangeRepository extends BasePriceRangeRepository
{
    /**
     * @var \App\Model\Store\StoreFacade
     */
    private $storeFacade;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @param \App\Model\Product\ProductRepository $productRepository
     * @param \Shopsys\FrameworkBundle\Component\Doctrine\QueryBuilderExtender $queryBuilderExtender
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Store\StoreFacade $storeFacade
     */
    public function __construct(
        ProductRepository $productRepository,
        QueryBuilderExtender $queryBuilderExtender,
        PricingGroupFacade $pricingGroupFacade,
        StoreFacade $storeFacade
    ) {
        parent::__construct($productRepository, $queryBuilderExtender);
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->storeFacade = $storeFacade;
    }

    /**
     * The only difference with the parent method is that here we use getOfferedInCategoryQueryBuilder instead of getListableInCategoryQueryBuilder
     * so variant prices are included in the filter
     *
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \App\Model\Category\Category $category
     * @param \App\Model\Product\Flag\Flag[] $onlyFlags
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\PriceRange
     */
    public function getPriceRangeInCategory($domainId, PricingGroup $pricingGroup, Category $category, array $onlyFlags = [])
    {
        $productsQueryBuilder = $this->productRepository->getOfferedInCategoryQueryBuilder(
            $domainId,
            $pricingGroup,
            $category,
            $onlyFlags
        );

        return $this->getPriceRangeByProductsQueryBuilder($productsQueryBuilder, $pricingGroup);
    }

    /**
     * The only difference with the parent method is that here we use getOfferedBySearchTextQueryBuilder instead of getListableBySearchTextQueryBuilder
     * so variant prices are included in the filter
     *
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param string $locale
     * @param string|null $searchText
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\PriceRange
     */
    public function getPriceRangeForSearch($domainId, PricingGroup $pricingGroup, $locale, $searchText)
    {
        $productsQueryBuilder = $this->productRepository
            ->getOfferedBySearchTextQueryBuilder($domainId, $pricingGroup, $locale, $searchText);

        return $this->getPriceRangeByProductsQueryBuilder($productsQueryBuilder, $pricingGroup);
    }

    /**
     * Takes into account sale prices
     *
     * @param \Doctrine\ORM\QueryBuilder $productsQueryBuilder
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \Shopsys\FrameworkBundle\Model\Product\Filter\PriceRange
     */
    protected function getPriceRangeByProductsQueryBuilder(QueryBuilder $productsQueryBuilder, PricingGroup $pricingGroup)
    {
        $customerPricingGroupPriceRange = parent::getPriceRangeByProductsQueryBuilder($productsQueryBuilder, $pricingGroup);
        $minCustomerPricingGroupPrice = $customerPricingGroupPriceRange->getMinimalPrice();
        $maxCustomerPricingGroupPrice = $customerPricingGroupPriceRange->getMaximalPrice();

        $domainId = $pricingGroup->getDomainId();
        $queryBuilder = clone $productsQueryBuilder;

        $this->queryBuilderExtender->addOrExtendJoin($queryBuilder, ProductCalculatedPrice::class, 'pcp', 'pcp.product = p');
        $this->queryBuilderExtender->addOrExtendJoin($queryBuilder, ProductStoreStock::class, 'pss', 'pss.product = p')
            ->andWhere('pcp.pricingGroup = :pricingGroup')
            ->andWhere('pss.stockQuantity > 0')
            ->andWhere('pss.store IN (:saleStores)')
            ->setParameter('saleStores', $this->storeFacade->getAllSaleStocks())
            ->setParameter('pricingGroup', $this->pricingGroupFacade->getSalePricePricingGroup($domainId))
            ->resetDQLPart('groupBy')
            ->resetDQLPart('orderBy')
            ->select('MIN(pcp.priceWithVat) AS minimalPrice, MAX(pcp.priceWithVat) AS maximalPrice');

        $priceRangeData = $queryBuilder->getQuery()->execute();
        $priceRangeDataRow = reset($priceRangeData);

        $minSalePrice = Money::create($priceRangeDataRow['minimalPrice'] ?? 0);
        $maxSalePrice = Money::create($priceRangeDataRow['maximalPrice'] ?? 0);

        $minPrice = $minSalePrice->isLessThan($minCustomerPricingGroupPrice) ? $minSalePrice : $minCustomerPricingGroupPrice;
        $maxPrice = $maxSalePrice->isGreaterThan($maxCustomerPricingGroupPrice) ? $maxSalePrice : $maxCustomerPricingGroupPrice;

        return new PriceRange($minPrice, $maxPrice);
    }
}
