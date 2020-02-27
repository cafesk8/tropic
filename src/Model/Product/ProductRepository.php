<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Model\Product\MainVariantGroup\MainVariantGroup;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPrice;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomain;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository as BaseProductRepository;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibility;

/**
 * @method \App\Model\Product\Product|null findById(int $id)
 * @method \Doctrine\ORM\QueryBuilder getAllListableQueryBuilder(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method \Doctrine\ORM\QueryBuilder getAllSellableQueryBuilder(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method \Doctrine\ORM\QueryBuilder getAllOfferedQueryBuilder(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method \Doctrine\ORM\QueryBuilder getAllVisibleQueryBuilder(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method \Doctrine\ORM\QueryBuilder getListableInCategoryQueryBuilder(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, \App\Model\Category\Category $category)
 * @method \Doctrine\ORM\QueryBuilder getListableForBrandQueryBuilder(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, \App\Model\Product\Brand\Brand $brand)
 * @method \Doctrine\ORM\QueryBuilder getSellableInCategoryQueryBuilder(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, \App\Model\Category\Category $category)
 * @method \Doctrine\ORM\QueryBuilder getOfferedInCategoryQueryBuilder(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, \App\Model\Category\Category $category)
 * @method \Doctrine\ORM\QueryBuilder getListableBySearchTextQueryBuilder(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, string $locale, string|null $searchText)
 * @method filterByCategory(\Doctrine\ORM\QueryBuilder $queryBuilder, \App\Model\Category\Category $category, int $domainId)
 * @method filterByBrand(\Doctrine\ORM\QueryBuilder $queryBuilder, \App\Model\Product\Brand\Brand $brand)
 * @method \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult getPaginationResultForListableInCategory(\App\Model\Category\Category $category, int $domainId, string $locale, \App\Model\Product\Filter\ProductFilterData $productFilterData, string $orderingModeId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, int $page, int $limit)
 * @method \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult getPaginationResultForListableForBrand(\App\Model\Product\Brand\Brand $brand, int $domainId, string $locale, string $orderingModeId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, int $page, int $limit)
 * @method \Doctrine\ORM\QueryBuilder getFilteredListableInCategoryQueryBuilder(\App\Model\Category\Category $category, int $domainId, string $locale, \App\Model\Product\Filter\ProductFilterData $productFilterData, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult getPaginationResultForSearchListable(string|null $searchText, int $domainId, string $locale, \App\Model\Product\Filter\ProductFilterData $productFilterData, string $orderingModeId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, int $page, int $limit)
 * @method \Doctrine\ORM\QueryBuilder getFilteredListableForSearchQueryBuilder(string|null $searchText, int $domainId, string $locale, \App\Model\Product\Filter\ProductFilterData $productFilterData, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method applyOrdering(\Doctrine\ORM\QueryBuilder $queryBuilder, string $orderingModeId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, string $locale)
 * @method \App\Model\Product\Product getById(int $id)
 * @method \App\Model\Product\Product[] getAllByIds(int[] $ids)
 * @method \App\Model\Product\Product getVisible(int $id, int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method \App\Model\Product\Product getSellableById(int $id, int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method \Doctrine\ORM\Internal\Hydration\IterableResult|\App\Model\Product\Product[][] getProductIteratorForReplaceVat()
 * @method \Doctrine\ORM\Internal\Hydration\IterableResult|\App\Model\Product\Product[][] getProductsForPriceRecalculationIterator()
 * @method \Doctrine\ORM\Internal\Hydration\IterableResult|\App\Model\Product\Product[][] getProductsForAvailabilityRecalculationIterator()
 * @method \App\Model\Product\Product[] getAllSellableVariantsByMainVariant(\App\Model\Product\Product $mainVariant, int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method \Doctrine\ORM\QueryBuilder getAllSellableUsingStockInStockQueryBuilder(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method \App\Model\Product\Product[] getAtLeastSomewhereSellableVariantsByMainVariant(\App\Model\Product\Product $mainVariant)
 * @method \App\Model\Product\Product[] getOfferedByIds(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, int[] $sortedProductIds)
 * @method \App\Model\Product\Product[] getListableByIds(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, int[] $sortedProductIds)
 * @method \App\Model\Product\Product getOneByCatnumExcludeMainVariants(string $productCatnum)
 * @method \App\Model\Product\Product getOneByUuid(string $uuid)
 * @method \Doctrine\ORM\QueryBuilder getAllListableTranslatedAndOrderedQueryBuilder(int $domainId, string $locale, string $orderingModeId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method \Doctrine\ORM\QueryBuilder getAllListableTranslatedAndOrderedQueryBuilderByCategory(int $domainId, string $locale, string $orderingModeId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, \App\Model\Category\Category $category)
 * @method array getAllOfferedProducts(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @property \App\Model\Product\Search\ProductElasticsearchRepository $productElasticsearchRepository
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \Shopsys\FrameworkBundle\Model\Product\Filter\ProductFilterRepository $productFilterRepository, \Shopsys\FrameworkBundle\Component\Doctrine\QueryBuilderExtender $queryBuilderExtender, \Shopsys\FrameworkBundle\Model\Localization\Localization $localization, \App\Model\Product\Search\ProductElasticsearchRepository $productElasticsearchRepository)
 */
class ProductRepository extends BaseProductRepository
{
    /**
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param array $ids
     * @return \App\Model\Product\Product[]
     */
    public function getVisibleMainVariantsByIds(int $domainId, PricingGroup $pricingGroup, array $ids): array
    {
        return $this->getAllVisibleQueryBuilder($domainId, $pricingGroup)
            ->andWhere('p.id IN(:productIds)')
            ->andWhere('p.variantType = :variantTypeMain OR p.variantType = :variantTypeNone')
            ->setParameter('productIds', $ids)
            ->setParameter('variantTypeMain', Product::VARIANT_TYPE_MAIN)
            ->setParameter('variantTypeNone', Product::VARIANT_TYPE_NONE)
            ->getQuery()
            ->execute();
    }

    /**
     * @param \App\Model\Product\Product[] $mainVariants
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Product[]
     */
    public function getAllSellableVariantsForMainVariants(array $mainVariants, $domainId, PricingGroup $pricingGroup): array
    {
        $queryBuilder = $this->getAllSellableQueryBuilder($domainId, $pricingGroup);
        $queryBuilder
            ->andWhere('p.mainVariant IN (:mainVariants)')
            ->setParameter('mainVariants', $mainVariants);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param \App\Model\Product\Parameter\Parameter $parameter
     * @return \App\Model\Product\Product[]
     */
    public function getProductsWithDistinguishingParameter(Parameter $parameter): array
    {
        return $this->getProductRepository()->findBy([
            'distinguishingParameter' => $parameter,
        ]);
    }

    /**
     * @param string $transferNumber
     * @return \App\Model\Product\Product|null
     */
    public function findByTransferNumber(string $transferNumber): ?Product
    {
        return $this->getProductRepository()->findOneBy(['transferNumber' => $transferNumber]);
    }

    /**
     * @param \App\Model\Product\Product[] $products
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Product[][]
     */
    public function getVariantsIndexedByMainVariantId(array $products, int $domainId, PricingGroup $pricingGroup): array
    {
        $queryBuilder = $this->getAllSellableQueryBuilder($domainId, $pricingGroup);
        $queryBuilder
            ->andWhere('p.mainVariant IN (:mainVariants)')
            ->setParameter('mainVariants', $products);

        $queryResult = $queryBuilder->getQuery()->execute();

        $results = [];

        /** @var \App\Model\Product\Product $product */
        foreach ($queryResult as $product) {
            $results[$product->getMainVariant()->getId()][] = $product;
        }

        return $results;
    }

    /**
     * @param \App\Model\Product\MainVariantGroup\MainVariantGroup $mainVariantGroup
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Product[]
     */
    public function getVariantsForMainVariantGroup(MainVariantGroup $mainVariantGroup, int $domainId, PricingGroup $pricingGroup): array
    {
        $queryBuilder = $this->getAllSellableQueryBuilder($domainId, $pricingGroup);
        $queryBuilder
            ->leftJoin('p.mainVariant', 'pmv')
            ->andWhere('p.variantType = :variant')
            ->andWhere('pmv.mainVariantGroup = :mainVariantGroup')
            ->andWhere('p.mallExport = true')
            ->setParameter('variant', Product::VARIANT_TYPE_VARIANT)
            ->setParameter('mainVariantGroup', $mainVariantGroup);

        $queryResult = $queryBuilder->getQuery()->execute();

        $results = [];

        /** @var \App\Model\Product\Product $variant */
        foreach ($queryResult as $variant) {
            $results[] = $variant;
        }

        return $results;
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getProductQueryBuilder(): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p');
    }

    /**
     * @param int $limit
     * @param int $page
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getWithEanQueryBuilder(int $limit, int $page): QueryBuilder
    {
        $offset = $limit * $page;

        return $this->getProductQueryBuilder()
            ->where('p.ean IS NOT NULL')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('p.id', 'ASC');
    }

    /**
     * @param int $limit
     * @param int $page
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getWithCatnumQueryBuilder(int $limit, int $page): QueryBuilder
    {
        $offset = $limit * $page;

        return $this->getProductQueryBuilder()
            ->where('p.catnum IS NOT NULL')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('p.id', 'ASC');
    }

    /**
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function getWithEan(int $limit, int $page): array
    {
        return $this->getWithEanQueryBuilder($limit, $page)->getQuery()->getResult();
    }

    /**
     * @param int $limit
     * @param int $page
     * @return \App\Model\Product\Product[]
     */
    public function getMainVariantsWithCatnum(int $limit, int $page): array
    {
        return $this->getWithCatnumQueryBuilder($limit, $page)
            ->andWhere('p.variantType = :mainVariantType')
            ->setParameter('mainVariantType', Product::VARIANT_TYPE_MAIN)
            ->getQuery()->getResult();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param int|null $lastSeekId
     * @param int $maxResults
     * @return \App\Model\Product\Product[]|\Doctrine\Common\Collections\ArrayCollection
     */
    public function getProductsForHsSportXmlFeed(DomainConfig $domainConfig, PricingGroup $pricingGroup, ?int $lastSeekId, int $maxResults): iterable
    {
        $queryBuilder = $this->getAllVisibleQueryBuilder($domainConfig->getId(), $pricingGroup)
            ->addSelect('b')->leftJoin('p.brand', 'b')
            ->andWhere('p.variantType IN (:variantTypes)')
            ->setParameter('variantTypes', [Product::VARIANT_TYPE_MAIN, Product::VARIANT_TYPE_NONE])
            ->andWhere('p.calculatedSellingDenied = false')
            ->andWhere('p.generateToHsSportXmlFeed = true')
            ->orderBy('p.id', 'asc')
            ->setMaxResults($maxResults);

        $this->addTranslation($queryBuilder, $domainConfig->getLocale());
        $this->addDomain($queryBuilder, $domainConfig->getId());
        $queryBuilder->addSelect('v')->join('pd.vat', 'v');

        if ($lastSeekId !== null) {
            $queryBuilder->andWhere('p.id > :lastProductId')->setParameter('lastProductId', $lastSeekId);
        }

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param string $ean
     * @return \App\Model\Product\Product|null
     */
    public function findOneNotMainVariantByEan(string $ean): ?Product
    {
        return $this->getProductRepository()
            ->createQueryBuilder('p')
            ->where('p.ean = :ean')
            ->andWhere('p.variantType != :mainVariantType')
            ->setParameters([
                'ean' => $ean,
                'mainVariantType' => Product::VARIANT_TYPE_MAIN,
            ])
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $catnum
     * @return \App\Model\Product\Product[]
     */
    public function getByCatnum(string $catnum): array
    {
        return $this->getProductRepository()->findBy(['catnum' => $catnum]);
    }

    /**
     * @param int $limit
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Product[]
     */
    public function getProductsForExportToMall(int $limit, int $domainId, PricingGroup $pricingGroup): array
    {
        return $this->getAllVisibleQueryBuilder($domainId, $pricingGroup)
            ->andWhere('p.mallExport = TRUE')
            ->andWhere('p.variantType IN (:mainOrNoneVariant)')
            ->setParameter('mainOrNoneVariant', [Product::VARIANT_TYPE_MAIN, Product::VARIANT_TYPE_NONE])
            ->andWhere('p.mallExportedAt is NULL OR p.mallExportedAt < p.updatedAt')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Product[]
     */
    public function getVariantsForProductExportToMall(Product $product, int $domainId, PricingGroup $pricingGroup): array
    {
        return $this->getAllVisibleQueryBuilder($domainId, $pricingGroup)
            ->andWhere('p.mallExport = TRUE')
            ->andWhere('p.variantType = :variant')
            ->andWhere('p.mainVariant = :mainVariant')
            ->setParameter('mainVariant', $product)
            ->setParameter('variant', Product::VARIANT_TYPE_VARIANT)
            ->getQuery()->getResult();
    }

    /**
     * @param int $domainId
     * @return \App\Model\Product\Product[]
     */
    public function getProductsToDeleteFromMall(int $domainId): array
    {
        $queryBuilder = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->join(ProductVisibility::class, 'prv', Join::WITH, 'prv.product = p.id')
            ->where('prv.domainId = :domainId')
            ->andWhere('p.mallExport = FALSE')
            ->andWhere('p.mallExportedAt is NOT NULL AND p.mallExportedAt < p.updatedAt');

        $queryBuilder->setParameter('domainId', $domainId);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param \App\Model\Product\Product $mainVariant
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return int
     */
    public function getCountOfVisibleVariantsForMainVariant(Product $mainVariant, int $domainId, PricingGroup $pricingGroup): int
    {
        return (int)$this->getAllVisibleQueryBuilder($domainId, $pricingGroup)
            ->select('count(p)')
            ->andWhere('p.mainVariant = :mainVariant OR p.mainVariantGroup = :mainVariantGroup')
            ->andWhere('p.variantType = :variant')
            ->setParameter('mainVariant', $mainVariant)
            ->setParameter('mainVariantGroup', $mainVariant->getMainVariantGroup())
            ->setParameter('variant', Product::VARIANT_TYPE_VARIANT)
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param string $parameterType
     * @param int $limit
     * @return \App\Model\Product\Product[]
     */
    public function getAllMainVariantProductsWithoutSkOrDeParameters(string $parameterType, int $limit): array
    {
        $queryBuilder = $this->em->createQueryBuilder()
            ->select('IDENTITY(ppv.product) as id')
            ->from(ProductParameterValue::class, 'ppv')
            ->join(Parameter::class, 'p', Join::WITH, 'ppv.parameter = p.id AND p.type = :parameterType')
            ->setParameter('parameterType', $parameterType)
            ->groupBy('ppv.product, ppv.parameter')
            ->having('COUNT(IDENTITY(ppv)) < 3')
            ->setMaxResults($limit);

        $productIdsInArray = $queryBuilder->getQuery()->getScalarResult();
        $productIds = array_column($productIdsInArray, 'id');

        return $this->findByIds($productIds);
    }

    /**
     * @param array $productIds
     * @return \App\Model\Product\Product[]
     */
    private function findByIds(array $productIds): array
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->andWhere('p.id IN(:productIds)')
            ->setParameter('productIds', $productIds)
            ->getQuery()->getResult();
    }

    /**
     * @param array $brandIds
     * @return \App\Model\Product\Product[]
     */
    public function getByBrandIds(array $brandIds): array
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->where('IDENTITY(p.brand) IN (:brandIds)')
            ->setParameter('brandIds', $brandIds)
            ->getQuery()->getResult();
    }

    /**
     * @param array $categoryIds
     * @param int $domainId
     * @return \App\Model\Product\Product[]
     */
    public function getByCategoryIds(array $categoryIds, int $domainId): array
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(ProductCategoryDomain::class, 'pc')
            ->join(Product::class, 'p', Join::WITH, 'pc.product = p.id')
            ->where('IDENTITY(pc.category) IN (:categoryIds)')
            ->andWhere('pc.domainId = :domainId')
            ->setParameter('categoryIds', $categoryIds)
            ->setParameter('domainId', $domainId)
            ->getQuery()->getResult();
    }

    /**
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return array
     */
    public function getMainVariantIdsWithDifferentPrice(int $domainId, PricingGroup $pricingGroup): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('main_variant_id', 'mainVariantId');
        $resultSetMapping->addScalarResult('default_price', 'defaultPrice');

        $queryBuilder = $this->em->createNativeQuery(
            'SELECT 
                    p.main_variant_id as main_variant_id,
                    (
                        SELECT sub_ip.input_price
                        FROM products sub_p
                        JOIN product_manual_input_prices sub_ip ON sub_ip.product_id = sub_p.id AND sub_ip.pricing_group_id = :pricingGroup
                        JOIN product_visibilities sub_pv ON sub_p.id = sub_pv.product_id AND sub_pv.pricing_group_id = :pricingGroup AND sub_pv.domain_id = :domainId
                        WHERE p.main_variant_id = sub_p.main_variant_id 
                        AND sub_pv.visible = true
                        GROUP BY sub_ip.input_price
                        ORDER BY COUNT(*) DESC, sub_ip.input_price DESC
                        LIMIT 1
                    ) as default_price
                FROM products p
                JOIN product_manual_input_prices ip ON ip.product_id = p.id AND ip.pricing_group_id = :pricingGroup
                JOIN product_visibilities pv ON p.id = pv.product_id AND pv.pricing_group_id = :pricingGroup AND pv.domain_id = :domainId
                WHERE p.main_variant_id IS NOT NULL
                AND pv.visible = true
                GROUP BY p.main_variant_id
                HAVING COUNT(DISTINCT ip.input_price) > 1',
            $resultSetMapping
        );

        $queryBuilder->setParameters([
            'pricingGroup' => $pricingGroup,
            'domainId' => $domainId,
        ]);

        return $queryBuilder->getResult();
    }

    /**
     * @param int $mainVariantId
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $defaultPrice
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Product[]
     */
    public function getVariantsWithDifferentPriceForMainVariant(int $mainVariantId, Money $defaultPrice, PricingGroup $pricingGroup): array
    {
        $variantsToHide = $this->getProductRepository()->createQueryBuilder('p')
            ->join(ProductManualInputPrice::class, 'pmip', Join::WITH, 'pmip.product = p.id AND pmip.pricingGroup = :pricingGroup')
            ->where('p.mainVariant = :mainVariant')
            ->andWhere('pmip.inputPrice != :defaultInputPrice')
            ->setParameters([
                'mainVariant' => $mainVariantId,
                'pricingGroup' => $pricingGroup,
                'defaultInputPrice' => $defaultPrice->getAmount(),
            ])->getQuery()->getResult();

        return $variantsToHide;
    }

    /**
     * @param int $limit
     * @param int $page
     * @return \App\Model\Product\Product[]
     */
    public function getMainVariantsWithEan(int $limit, int $page): array
    {
        return $this->getWithEanQueryBuilder($limit, $page)
            ->andWhere('p.variantType = :mainVariantType')
            ->setParameter('mainVariantType', Product::VARIANT_TYPE_MAIN)
            ->getQuery()->getResult();
    }
}
