<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Component\Domain\DomainHelper;
use App\Model\Category\Category;
use App\Model\Pricing\Group\PricingGroup;
use App\Model\Product\Set\ProductSet;
use DateTime;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Category\Category as BaseCategory;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup as BasePricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Flag\Flag;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValue;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPrice;
use Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomain;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository as BaseProductRepository;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibility;

/**
 * @method \App\Model\Product\Product|null findById(int $id)
 * @method \Doctrine\ORM\QueryBuilder getAllListableQueryBuilder(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method \Doctrine\ORM\QueryBuilder getAllSellableQueryBuilder(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method \Doctrine\ORM\QueryBuilder getAllOfferedQueryBuilder(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method \Doctrine\ORM\QueryBuilder getAllVisibleQueryBuilder(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method \Doctrine\ORM\QueryBuilder getListableForBrandQueryBuilder(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, \App\Model\Product\Brand\Brand $brand)
 * @method \Doctrine\ORM\QueryBuilder getSellableInCategoryQueryBuilder(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, \App\Model\Category\Category $category)
 * @method filterByBrand(\Doctrine\ORM\QueryBuilder $queryBuilder, \App\Model\Product\Brand\Brand $brand)
 * @method \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult getPaginationResultForListableInCategory(\App\Model\Category\Category $category, int $domainId, string $locale, \App\Model\Product\Filter\ProductFilterData $productFilterData, string $orderingModeId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, int $page, int $limit)
 * @method \Doctrine\ORM\QueryBuilder getAllListableTranslatedAndOrderedQueryBuilder(int $domainId, string $locale, string $orderingModeId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method \Doctrine\ORM\QueryBuilder getAllListableTranslatedAndOrderedQueryBuilderByCategory(int $domainId, string $locale, string $orderingModeId, \App\Model\Pricing\Group\PricingGroup $pricingGroup, \App\Model\Category\Category $category)
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
 * @method \App\Model\Product\Product getOneByCatnumExcludeMainVariants(string $productCatnum)
 * @method \App\Model\Product\Product getOneByUuid(string $uuid)
 * @method array getAllOfferedProducts(int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @method markProductsForExport(\App\Model\Product\Product[] $products)
 * @method array getProductsWithParameter(\App\Model\Product\Parameter\Parameter $parameter)
 * @method \App\Model\Product\Product[] getProductsWithAvailability(\App\Model\Product\Availability\Availability $availability)
 * @method \App\Model\Product\Product[] getProductsWithBrand(\App\Model\Product\Brand\Brand $brand)
 * @method \App\Model\Product\Product[] getProductsWithUnit(\App\Model\Product\Unit\Unit $unit)
 * @method \App\Model\Product\Product getSellableByUuid(string $uuid, int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
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
     * @param int $pohodaId
     * @return \App\Model\Product\Product|null
     */
    public function findByPohodaId(int $pohodaId): ?Product
    {
        return $this->getProductRepository()->findOneBy(['pohodaId' => $pohodaId]);
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
    public function getProductsForMergadoXmlFeed(DomainConfig $domainConfig, PricingGroup $pricingGroup, ?int $lastSeekId, int $maxResults): iterable
    {
        $queryBuilder = $this->getAllVisibleQueryBuilder($domainConfig->getId(), $pricingGroup)
            ->addSelect('b')->leftJoin('p.brand', 'b')
            ->andWhere('pd.generateToMergadoXmlFeed = true')
            ->andWhere('p.variantType != :mainVariantType')
            ->orderBy('p.id', 'asc')
            ->setParameter('mainVariantType', Product::VARIANT_TYPE_MAIN)
            ->setMaxResults($maxResults);

        $this->addTranslation($queryBuilder, $domainConfig->getLocale());
        $this->addDomain($queryBuilder, $domainConfig->getId());

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
            ->andWhere('p.mainVariant = :mainVariant')
            ->andWhere('p.variantType = :variant')
            ->setParameter('mainVariant', $mainVariant)
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
    public function findByIds(array $productIds): array
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

    /**
     * @param string $mainVariantVariantId
     * @return \App\Model\Product\Product|null
     */
    public function findMainVariantByVariantId(string $mainVariantVariantId): ?Product
    {
        /** @var \App\Model\Product\Product|null $mainVariant */
        $mainVariant = $this->getProductRepository()->findOneBy([
            'variantType' => Product::VARIANT_TYPE_MAIN,
            'variantId' => $mainVariantVariantId,
        ]);

        return $mainVariant;
    }

    /**
     * @param string $variantId
     * @return \App\Model\Product\Product|null
     */
    public function findByVariantId(string $variantId): ?Product
    {
        /** @var \App\Model\Product\Product|null $product */
        $product = $this->getProductRepository()->findOneBy([
            'variantId' => $variantId,
        ]);

        return $product;
    }

    /**
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param string $locale
     * @param string|null $searchText
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getOfferedBySearchTextQueryBuilder(
        int $domainId,
        BasePricingGroup $pricingGroup,
        string $locale,
        ?string $searchText
    ): QueryBuilder {
        $queryBuilder = $this->getAllOfferedQueryBuilder($domainId, $pricingGroup);

        $this->addTranslation($queryBuilder, $locale);
        $this->addDomain($queryBuilder, $domainId);

        $this->productElasticsearchRepository->filterBySearchText($queryBuilder, $searchText);

        return $queryBuilder;
    }

    /**
     * @param \App\Model\Product\Product $mainVariant
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Product[]
     */
    public function getAllVisibleVariantsByMainVariant(Product $mainVariant, int $domainId, PricingGroup $pricingGroup): array
    {
        $queryBuilder = $this->getAllVisibleQueryBuilder($domainId, $pricingGroup);
        $queryBuilder
            ->andWhere('p.mainVariant = :mainVariant')
            ->setParameter('mainVariant', $mainVariant);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param int $domainId
     * @param \App\Model\Category\Category $category
     * @return bool
     */
    public function isAnyListableProductInCategoryIndependentOfPricingGroup(int $domainId, Category $category): bool
    {
        $queryBuilder = $this->em->createQueryBuilder()
            ->select('p.id')
            ->from(Product::class, 'p')
            ->join(ProductVisibility::class, 'prv', Join::WITH, 'prv.product = p.id')
            ->where('prv.domainId = :domainId')
            ->andWhere('prv.visible = TRUE')
            ->andWhere('p.calculatedSellingDenied = FALSE')
            ->andWhere('p.variantType != :variantTypeVariant')
            ->setParameter('domainId', $domainId)
            ->setParameter('variantTypeVariant', Product::VARIANT_TYPE_VARIANT)
            ->orderBy('p.id')
            ->setMaxResults(1);
        $this->filterByCategory($queryBuilder, $category, $domainId);

        try {
            $queryBuilder->getQuery()->getSingleScalarResult();
            return true;
        } catch (NoResultException $exception) {
            return false;
        }
    }

    /**
     * @param string $mainVariantId
     * @return \App\Model\Product\Product[]
     */
    public function getVariantsByMainVariantId(string $mainVariantId): array
    {
        return $this->getProductQueryBuilder()
            ->join(Product::class, 'productMainVariant', Join::WITH, 'p.mainVariant = productMainVariant')
            ->andWhere('productMainVariant.variantId = :mainVariantId')
            ->setParameter('mainVariantId', $mainVariantId)
            ->getQuery()
            ->execute();
    }

    /**
     * @return \App\Model\Product\Product[]
     */
    public function getProductsForRefresh(): array
    {
        return $this->getProductRepository()->findBy(['refresh' => true], null, 500);
    }

    /**
     * @param \App\Model\Product\Flag\Flag $flag
     * @return \App\Model\Product\Product[]
     */
    public function getProductsWithFlag(Flag $flag): array
    {
        return $this->getProductRepository()->createQueryBuilder('p')
            ->leftJoin('p.flags', 'pf')
            ->where('pf.flag = :flag')
            ->setParameter('flag', $flag)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return \App\Model\Product\ProductDomain[]
     */
    public function getProductDomainsForDescriptionTranslation(): array
    {
        return $this->em->createQueryBuilder()
            ->select('pd')
            ->from(Product::class, 'p')
            ->join(ProductDomain::class, 'pd', Join::WITH, 'pd.product = p')
            ->where('(MD5(pd.description) != pd.descriptionHash OR pd.descriptionHash IS NULL)')
            ->andWhere('pd.description IS NOT NULL')
            ->andWhere('pd.description != \'\'')
            ->andWhere('p.variantType != \'variant\'')
            ->andWhere('p.descriptionAutomaticallyTranslated = TRUE')
            ->andWhere('pd.domainId = 1')
            ->getQuery()->execute();
    }

    /**
     * @return \App\Model\Product\ProductDomain[]
     */
    public function getProductDomainsForShortDescriptionTranslation(): array
    {
        return $this->em->createQueryBuilder()
            ->select('pd')
            ->from(Product::class, 'p')
            ->join(ProductDomain::class, 'pd', Join::WITH, 'pd.product = p')
            ->where('(MD5(pd.shortDescription) != pd.shortDescriptionHash OR pd.shortDescriptionHash IS NULL)')
            ->andWhere('pd.shortDescription IS NOT NULL')
            ->andWhere('pd.shortDescription != \'\'')
            ->andWhere('p.variantType != \'variant\'')
            ->andWhere('p.shortDescriptionAutomaticallyTranslated = TRUE')
            ->andWhere('pd.domainId = 1')
            ->getQuery()->execute();
    }

    /**
     * @param \DateTime|null $dateTime
     * @return int[]
     */
    public function getPohodaIdsForProductsUpdatedSince(?DateTime $dateTime): array
    {
        $result = $this->getProductQueryBuilder()
            ->select('p.pohodaId')
            ->where('p.pohodaId IS NOT NULL')
            ->andWhere('p.updatedByPohodaAt > :dateTime')
            ->setParameter(':dateTime', $dateTime ?? new DateTime('1970-01-01'))
            ->getQuery()
            ->execute();

        return array_column($result, 'pohodaId');
    }

    /**
     * Override removes product groups from filters
     *
     * @inheritDoc
     */
    public function getListableBySearchTextQueryBuilder($domainId, BasePricingGroup $pricingGroup, $locale, $searchText)
    {
        $queryBuilder = parent::getListableBySearchTextQueryBuilder($domainId, $pricingGroup, $locale, $searchText);
        $queryBuilder
            ->andWhere('(p.pohodaProductType != :pohodaGroupType OR p.pohodaProductType IS NULL)')
            ->setParameter('pohodaGroupType', Product::POHODA_PRODUCT_TYPE_ID_PRODUCT_SET);

        return $queryBuilder;
    }

    /**
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \App\Model\Category\Category $category
     * @param \App\Model\Product\Flag\Flag[] $onlyFlags
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getListableInCategoryQueryBuilder(
        $domainId,
        BasePricingGroup $pricingGroup,
        BaseCategory $category,
        array $onlyFlags = []
    ) {
        $queryBuilder = $this->getAllListableQueryBuilder($domainId, $pricingGroup);
        $this->filterByCategory($queryBuilder, $category, $domainId, $onlyFlags);
        return $queryBuilder;
    }

    /**
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \App\Model\Category\Category $category
     * @param \App\Model\Product\Flag\Flag[] $onlyFlags
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getOfferedInCategoryQueryBuilder(
        $domainId,
        BasePricingGroup $pricingGroup,
        BaseCategory $category,
        array $onlyFlags = []
    ) {
        $queryBuilder = $this->getAllOfferedQueryBuilder($domainId, $pricingGroup);
        $this->filterByCategory($queryBuilder, $category, $domainId, $onlyFlags);

        return $queryBuilder;
    }

    /**
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \App\Model\Category\Category $category
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getVisibleSetItemsInCategoryQueryBuilder(
        int $domainId,
        PricingGroup $pricingGroup,
        Category $category
    ): QueryBuilder {
        $queryBuilder = $this->getAllOfferedQueryBuilder($domainId, $pricingGroup)
            ->andWhere('p.pohodaProductType = :pohodaSetType')
            ->join(ProductSet::class, 'ps', Join::WITH, 'ps.mainProduct = p')
            ->join(Product::class, 'setItem', Join::WITH, 'ps.item = setItem')
            ->setParameter('pohodaSetType', Product::POHODA_PRODUCT_TYPE_ID_PRODUCT_SET);
        $this->filterByCategory($queryBuilder, $category, $domainId);

        return $queryBuilder;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param \App\Model\Category\Category $category
     * @param int $domainId
     * @param \App\Model\Product\Flag\Flag[] $onlyFlags
     */
    protected function filterByCategory(QueryBuilder $queryBuilder, BaseCategory $category, $domainId, array $onlyFlags = [])
    {
        parent::filterByCategory($queryBuilder, $category, $domainId);

        if (!empty($onlyFlags)) {
            $queryBuilder->leftJoin('p.flags', 'pf');
            $queryBuilder->andWhere('pf.flag IN (:flags) AND (pf.activeFrom IS NULL OR pf.activeFrom <= :now) AND (pf.activeTo IS NULL OR pf.activeTo >= :now)');
            $queryBuilder->setParameter('flags', $onlyFlags);
            $queryBuilder->setParameter('now', new DateTime());
        }
    }

    /**
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param int[] $sortedProductIds
     * @return \App\Model\Product\Product[]
     */
    public function getListableByIds(int $domainId, BasePricingGroup $pricingGroup, array $sortedProductIds): array
    {
        if (count($sortedProductIds) === 0) {
            return [];
        }

        $queryBuilder = $this->getAllListableQueryBuilder($domainId, $pricingGroup);
        $queryBuilder
            ->andWhere('p.id IN (:productIds)')
            ->setParameter('productIds', $sortedProductIds)
            ->addSelect('field(p.id, ' . implode(',', $sortedProductIds) . ') AS HIDDEN relevance')
            ->orderBy('relevance');

        $query = $queryBuilder->getQuery();
        $query->useQueryCache(false);

        return $query->execute();
    }

    /**
     * @return \Doctrine\ORM\Internal\Hydration\IterableResult|\App\Model\Product\Product[][]
     */
    public function getProductIteratorForReplaceVat()
    {
        $ids = $this->em->createQuery('
            SELECT DISTINCT p.id
            FROM ' . Product::class . ' p
            JOIN ' . ProductDomain::class . ' pd WITH pd.product = p
            JOIN pd.vat v
            WHERE v.replaceWith IS NOT NULL
        ')->getResult(AbstractQuery::HYDRATE_ARRAY);

        $query = $this->em->createQuery('
            SELECT p
            FROM ' . Product::class . ' p
            WHERE p.id IN (:ids)
        ')->setParameter('ids', array_column($ids, 'id'));

        return $query->iterate();
    }

    /**
     * @param int $productId
     * @param int $domainId
     * @param string $description
     * @param string $descriptionHash
     */
    public function setDescriptionTranslation(int $productId, int $domainId, string $description, string $descriptionHash): void
    {
        $this->em->createNativeQuery('UPDATE product_domains 
                SET description = :description 
                WHERE product_id = :productId AND domain_id = :domainId',
            new ResultSetMapping()
        )->execute([
            'description' => $description,
            'productId' => $productId,
            'domainId' => $domainId,
        ]);
        $this->em->createNativeQuery('UPDATE product_domains 
                SET description_hash = :hash 
                WHERE product_id = :productId AND domain_id = :domainId',
            new ResultSetMapping()
        )->execute([
            'hash' => $descriptionHash,
            'productId' => $productId,
            'domainId' => DomainHelper::CZECH_DOMAIN,
        ]);
    }

    /**
     * @param int $productId
     * @param int $domainId
     * @param string $description
     * @param string $descriptionHash
     */
    public function setShortDescriptionTranslation(int $productId, int $domainId, string $description, string $descriptionHash): void
    {
        $this->em->createNativeQuery('UPDATE product_domains 
                SET short_description = :description 
                WHERE product_id = :productId AND domain_id = :domainId',
            new ResultSetMapping()
        )->execute([
            'description' => $description,
            'productId' => $productId,
            'domainId' => $domainId,
        ]);
        $this->em->createNativeQuery('UPDATE product_domains 
                SET short_description_hash = :hash 
                WHERE product_id = :productId AND domain_id = :domainId',
            new ResultSetMapping()
        )->execute([
            'hash' => $descriptionHash,
            'productId' => $productId,
            'domainId' => DomainHelper::CZECH_DOMAIN,
        ]);
    }

    /**
     * @param int[] $pohodaProductIds
     * @return array
     */
    public function getProductIdsIndexedByPohodaIds(array $pohodaProductIds): array
    {
        $resultSetMapping = new ResultSetMapping();
        $resultSetMapping->addScalarResult('id', 'id');
        $resultSetMapping->addScalarResult('pohoda_id', 'pohodaId');
        $resultSetMapping->addScalarResult('amount_multiplier', 'amountMultiplier');

        $query = $this->em->createNativeQuery(
            'SELECT id, pohoda_id, amount_multiplier
            FROM products
            WHERE pohoda_id IN (:pohodaProductIds)',
            $resultSetMapping
        )->setParameters([
            'pohodaProductIds' => $pohodaProductIds,
        ]);

        $productsResult = $query->getResult();
        $productIds = [];
        foreach ($productsResult as $productResult) {
            $productIds[(int)$productResult['pohodaId']] = [
                'productId' => (int)$productResult['id'],
                'amountMultiplier' => (int)$productResult['amountMultiplier'],
            ];
        }

        return $productIds;
    }

    /**
     * @param int $productId
     * @param int $stockQuantity
     * @param int $realStockQuantity
     */
    public function manualUpdateProductStockQuantities(int $productId, int $stockQuantity, int $realStockQuantity): void
    {
        $query = $this->em->createNativeQuery(
            'UPDATE products 
            SET stock_quantity = :stockQuantity, real_stock_quantity = :realStockQuantity, recalculate_availability = TRUE
            WHERE id = :productId',
            new ResultSetMapping()
        )->setParameters([
            'stockQuantity' => $stockQuantity,
            'realStockQuantity' => $realStockQuantity,
            'productId' => $productId,
        ]);
        $query->execute();
    }

    /**
     * @param int[] $pohodaIds
     */
    public function markAsExportedToElasticByPohodaIds(array $pohodaIds): void
    {
        foreach ($pohodaIds as $pohodaId) {
            $queryBuilder = $this->em->createQueryBuilder();
            $queryBuilder
                ->update(Product::class, 'p')
                ->set('p.exportProduct', 'false')
                ->where('p.pohodaId = :pohodaId')
                ->setParameter('pohodaId', $pohodaId)
                ->getQuery()
                ->execute();
        }
    }

    /**
     * @param int $domainId
     * @return \App\Model\Product\Product[]
     */
    public function getMarkedForLuigisBoxExport(int $domainId): array
    {
        return $this->getProductQueryBuilder()
            ->join(ProductDomain::class, 'pd', Join::WITH, 'pd.product = p')
            ->where('p.variantType != :variantType')
            ->andWhere('pd.domainId = :domainId')
            ->andWhere('pd.exportedToLuigisBox = FALSE')
            ->orderBy('p.updatedByPohodaAt', 'ASC')
            ->setMaxResults(500)
            ->getQuery()->execute([
                'domainId' => $domainId,
                'variantType' => Product::VARIANT_TYPE_VARIANT,
            ]);
    }

    /**
     * @param \App\Model\Product\Product[] $products
     * @param int $domainId
     */
    public function markAsExportedToLuigisBox(array $products, int $domainId): void
    {
        $this->em->createQueryBuilder()
            ->update(ProductDomain::class, 'pd')
            ->set('pd.exportedToLuigisBox', 'TRUE')
            ->where('pd.product IN (:products)')
            ->andWhere('pd.domainId = :domainId')
            ->getQuery()->execute([
                'domainId' => $domainId,
                'products' => $products,
            ]);
    }

    /**
     * @param int[] $productIds
     */
    public function markForExportToLuigisBoxByIds(array $productIds): void
    {
        $this->em->createNativeQuery('UPDATE product_domains 
            SET exported_to_luigis_box = FALSE
            WHERE product_id IN (:productIds)', new ResultSetMapping()
        )->execute(['productIds' => $productIds]);
    }

    /**
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Product[]
     */
    public function getProductsWithSalePrice(PricingGroup $pricingGroup): array
    {
        return $this->getProductRepository()->createQueryBuilder('p')
            ->select('p')
            ->join(ProductManualInputPrice::class, 'pmip', Join::WITH, 'p = pmip.product')
            ->where('pmip.pricingGroup = :pricingGroup')
            ->andWhere('pmip.inputPrice IS NOT NULL')
            ->andWhere('pmip.inputPrice != 0')
            ->setParameter('pricingGroup', $pricingGroup)
            ->getQuery()->getResult();
    }
}
