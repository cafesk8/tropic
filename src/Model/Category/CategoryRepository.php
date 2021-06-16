<?php

declare(strict_types=1);

namespace App\Model\Category;

use App\Model\Advert\Advert;
use App\Model\Product\Brand\Brand;
use App\Model\Product\Flag\Flag;
use DateTime;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\Paginator\PaginationResult;
use Shopsys\FrameworkBundle\Component\Paginator\QueryPaginator;
use Shopsys\FrameworkBundle\Model\Category\Category as BaseCategory;
use Shopsys\FrameworkBundle\Model\Category\CategoryDomain;
use Shopsys\FrameworkBundle\Model\Category\CategoryRepository as BaseCategoryRepository;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomain;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibility;

/**
 * @property \App\Model\Product\ProductRepository $productRepository
 * @method __construct(\Doctrine\ORM\EntityManagerInterface $em, \App\Model\Product\ProductRepository $productRepository, \Shopsys\FrameworkBundle\Component\EntityExtension\EntityNameResolver $entityNameResolver)
 * @method \App\Model\Category\Category[] getAll()
 * @method \App\Model\Category\Category[] getAllCategoriesOfCollapsedTree(\App\Model\Category\Category[] $selectedCategories)
 * @method \App\Model\Category\Category[] getFullPathsIndexedByIdsForDomain(int $domainId, string $locale)
 * @method \App\Model\Category\Category getRootCategory()
 * @method \App\Model\Category\Category[] getTranslatedAllWithoutBranch(\App\Model\Category\Category $categoryBranch, \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig)
 * @method \App\Model\Category\Category|null findById(int $categoryId)
 * @method \App\Model\Category\Category getById(int $categoryId)
 * @method \App\Model\Category\Category[] getPreOrderTreeTraversalForAllCategories(string $locale)
 * @method \App\Model\Category\Category[] getPreOrderTreeTraversalForVisibleCategoriesByDomain(int $domainId, string $locale)
 * @method \App\Model\Category\Category[] getTranslatedVisibleSubcategoriesByDomain(\App\Model\Category\Category $parentCategory, \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig)
 * @method \App\Model\Category\Category[] getVisibleByDomainIdAndSearchText(int $domainId, string $locale, string|null $searchText)
 * @method \App\Model\Category\Category[] getAllVisibleChildrenByCategoryAndDomainId(\App\Model\Category\Category $category, int $domainId)
 * @method int[] getListableProductCountsIndexedByCategoryId(\App\Model\Category\Category[] $categories, \App\Model\Pricing\Group\PricingGroup $pricingGroup, int $domainId)
 * @method \App\Model\Category\Category|null findProductMainCategoryOnDomain(\App\Model\Product\Product $product, int $domainId)
 * @method \App\Model\Category\Category getProductMainCategoryOnDomain(\App\Model\Product\Product $product, int $domainId)
 * @method \App\Model\Category\Category[] getVisibleCategoriesInPathFromRootOnDomain(\App\Model\Category\Category $category, int $domainId)
 * @method string[] getCategoryNamesInPathFromRootToProductMainCategoryOnDomain(\App\Model\Product\Product $product, \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig)
 * @method \App\Model\Category\Category[] getCategoriesByIds(int[] $categoryIds)
 * @method \App\Model\Category\Category[] getCategoriesWithVisibleChildren(\App\Model\Category\Category[] $categories, int $domainId)
 * @method \App\Model\Category\Category[] getTranslatedAll(\Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig)
 * @method \App\Model\Category\Category getOneByUuid(string $uuid)
 * @method \App\Model\Category\Category[] getAllTranslatedWithoutBranch(\App\Model\Category\Category $categoryBranch, string $locale)
 * @method \App\Model\Category\Category[] getAllTranslated(string $locale)
 */
class CategoryRepository extends BaseCategoryRepository
{
    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    private function getProductCategoryDomainRepository(): EntityRepository
    {
        return $this->em->getRepository(ProductCategoryDomain::class);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getProductVisibleAndListableProductCategoryDomainsQueryBuilder(Product $product, DomainConfig $domainConfig)
    {
        return $this->getProductCategoryDomainRepository()->createQueryBuilder('pcd')
            ->select('pcd')
            ->addSelect('c')
            ->addSelect('ct')
            ->join('pcd.category', 'c')
            ->join('c.translations', 'ct', Join::WITH, 'ct.locale = :locale')
            ->join('c.domains', 'cd')
            ->andWhere('pcd.product = :product')
            ->andWhere('pcd.domainId = :domainId')
            ->andWhere('cd.domainId = :domainId')
            ->andWhere('cd.visible = true')
            ->andWhere('c.listable = true')
            ->andWhere('c.parent IS NOT NULL')
            ->andWhere('cd.enabled = true')
            ->setParameter('product', $product)
            ->setParameter('domainId', $domainConfig->getId())
            ->setParameter('locale', $domainConfig->getLocale());
    }

    /**
     * @param string|null $searchText
     * @param int $domainId
     * @param string $locale
     * @param int $page
     * @param int $limit
     * @return \Shopsys\FrameworkBundle\Component\Paginator\PaginationResult
     */
    public function getPaginationResultForSearchVisibleAndListable(
        ?string $searchText,
        int $domainId,
        string $locale,
        int $page,
        int $limit
    ): PaginationResult {
        $queryBuilder = $this->getVisibleAndListableByDomainIdAndSearchTextQueryBuilder($domainId, $locale, $searchText);
        $queryBuilder->orderBy('ct.name');

        $queryPaginator = new QueryPaginator($queryBuilder);

        return $queryPaginator->getResult($page, $limit);
    }

    /**
     * @param int $domainId
     * @param string $locale
     * @param string|null $searchText
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getVisibleAndListableByDomainIdAndSearchTextQueryBuilder(
        int $domainId,
        string $locale,
        ?string $searchText
    ): QueryBuilder {
        $queryBuilder = $this->getAllVisibleAndListableByDomainIdQueryBuilder($domainId);
        $this->addTranslation($queryBuilder, $locale);
        $this->filterBySearchText($queryBuilder, $searchText);

        return $queryBuilder;
    }

    /**
     * @param int $domainId
     * @param string $locale
     * @param string|null $searchText
     * @return \App\Model\Category\Category[]
     */
    public function getVisibleAndListableByDomainIdAndSearchText(int $domainId, string $locale, ?string $searchText): array
    {
        $queryBuilder = $this->getVisibleAndListableByDomainIdAndSearchTextQueryBuilder(
            $domainId,
            $locale,
            $searchText
        );

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomain[]
     */
    public function getProductVisibleAndListableProductCategoryDomains(Product $product, DomainConfig $domainConfig): array
    {
        return $this->getProductVisibleAndListableProductCategoryDomainsQueryBuilder($product, $domainConfig)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @return string|null
     */
    public function findMallCategoryForProduct(Product $product, int $domainId): ?string
    {
        $queryBuilder = $this->getAllVisibleByDomainIdQueryBuilder($domainId)
            ->join(
                ProductCategoryDomain::class,
                'pcd',
                Join::WITH,
                'pcd.product = :product
                    AND pcd.category = c
                    AND pcd.domainId = :domainId'
            )
            ->select('c.mallCategoryId')
            ->andWhere('c.mallCategoryId is NOT NULL')
            ->orderBy('c.level DESC, c.lft')
            ->setMaxResults(1);

        $queryBuilder->setParameters([
            'domainId' => $domainId,
            'product' => $product,
        ]);

        return $queryBuilder->getQuery()->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * @param \App\Model\Category\Category $category
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return array
     */
    public function getAllVisibleAndListableChildrenByCategoryAndDomain(BaseCategory $category, DomainConfig $domainConfig): array
    {
        $queryBuilder = $this->getAllVisibleAndListableByDomainIdQueryBuilder($domainConfig->getId());

        $queryBuilder
            ->addSelect('ct')
            ->join('c.translations', 'ct', Join::WITH,'ct.locale = :locale')
            ->setParameter('locale', $domainConfig->getLocale());

        if ($category->isSaleType()) {
            $queryBuilder->andWhere('c.level = :level')
                ->andWhere('cd.containsSaleProduct = true')
                ->setParameter('level', CategoryFacade::SALE_CATEGORIES_LEVEL);
        }

        if ($category->isNewsType()) {
            $queryBuilder->andWhere('c.level = :level')
                ->andWhere('cd.containsNewsProduct = true')
                ->setParameter('level', CategoryFacade::NEWS_CATEGORIES_LEVEL);
        }

        if (!$category->isSaleType() && !$category->isNewsType()) {
            $queryBuilder->andWhere('c.parent = :category')
                ->setParameter('category', $category);
        }

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param \App\Model\Category\Category $parentCategory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \App\Model\Category\Category[]
     */
    public function getTranslatedVisibleAndListableSubcategoriesByDomain(BaseCategory $parentCategory, DomainConfig $domainConfig): array
    {
        $queryBuilder = $this->getAllVisibleAndListableByDomainIdQueryBuilder($domainConfig->getId());
        $this->addTranslation($queryBuilder, $domainConfig->getLocale());

        $queryBuilder
            ->andWhere('c.parent = :parentCategory')
            ->setParameter('parentCategory', $parentCategory);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param \App\Model\Category\Category[] $categories
     * @param int $domainId
     * @return \App\Model\Category\Category[]
     */
    public function getCategoriesWithVisibleAndListableChildren(array $categories, int $domainId): array
    {
        $queryBuilder = $this->getAllVisibleAndListableByDomainIdQueryBuilder($domainId);

        $queryBuilder
            ->join(BaseCategory::class, 'cc', Join::WITH, 'cc.parent = c')
            ->join(CategoryDomain::class, 'ccd', Join::WITH, 'ccd.category = cc.id')
            ->andWhere('ccd.domainId = :domainId')
            ->andWhere('ccd.visible = TRUE')
            ->andWhere('c IN (:categories)')
            ->setParameter('categories', $categories);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param int $domainId
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAllVisibleAndListableByDomainIdQueryBuilder(int $domainId): QueryBuilder
    {
        return $this->getAllVisibleByDomainIdQueryBuilder($domainId)->andWhere('c.listable = true');
    }

    /**
     * @param \App\Model\Advert\Advert $advert
     * @param \App\Model\Category\Category[] $newCategories
     */
    public function removeAdvertFromCategories(Advert $advert, array $newCategories): void
    {
        $queryBuilder = $this->getQueryBuilder()
            ->update(BaseCategory::class, 'c')
            ->set('c.advert', 'NULL')
            ->where('c.advert = :advert');

        if (!empty($newCategories)) {
            $queryBuilder->andWhere('c NOT IN (:categories)')
                ->setParameter('categories', $newCategories);
        }

        $queryBuilder->setParameter('advert', $advert)
            ->getQuery()->execute();
    }

    /**
     * @param \App\Model\Advert\Advert $advert
     * @return \App\Model\Category\Category[]
     */
    public function getCategoriesByAdvert(Advert $advert): array
    {
        return $this->getCategoryRepository()->findBy(['advert' => $advert]);
    }

    /**
     * @param int $pohodaId
     * @return \App\Model\Category\Category|null
     */
    public function findByPohodaId(int $pohodaId): ?BaseCategory
    {
        return $this->getCategoryRepository()->findOneBy(['pohodaId' => $pohodaId]);
    }

    /**
     * @return \App\Model\Category\Category[][]
     */
    public function getAllIndexedByIdGroupedByPohodaParentId(): array
    {
        $pohodaCategories = [];

        /** @var \App\Model\Category\Category[] $categories */
        $categories = $this->getCategoryRepository()->createQueryBuilder('c')
            ->where('c.pohodaParentId IS NOT NULL')
            ->orderBy('c.pohodaParentId, c.pohodaPosition', 'ASC')
            ->getQuery()->getResult();

        foreach ($categories as $category) {
            $pohodaParentId = $category->getPohodaParentId();

            $pohodaCategories[$pohodaParentId][$category->getId()] = $category;
        }

        return $pohodaCategories;
    }

    /**
     * @param array $pohodaIds
     * @return \App\Model\Category\Category[]
     */
    public function getCategoriesExceptPohodaIds(array $pohodaIds): array
    {
        $queryBuilder = $this->getAllQueryBuilder();
        if (count($pohodaIds) > 0) {
            $queryBuilder->andWhere('c.pohodaId IS NOT NULL AND c.pohodaId NOT IN (:pohodaIds)')
                ->setParameter('pohodaIds', $pohodaIds);
        }

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param string $type
     * @return \App\Model\Category\Category|null
     */
    public function findByType(string $type): ?Category
    {
        return $this->getCategoryRepository()->findOneBy(['type' => $type]);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return array
     */
    public function getAllVisibleAndListableSaleCategoriesByDomain(DomainConfig $domainConfig): array
    {
        $queryBuilder = $this->getAllVisibleAndListableByDomainIdQueryBuilder($domainConfig->getId())
            ->andWhere('c.level = :level')
            ->andWhere('cd.containsSaleProduct = true')
            ->setParameter('level', CategoryFacade::SALE_CATEGORIES_LEVEL);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return array
     */
    public function getAllVisibleAndListableNewsCategoriesByDomain(DomainConfig $domainConfig): array
    {
        $queryBuilder = $this->getAllVisibleAndListableByDomainIdQueryBuilder($domainConfig->getId())
            ->andWhere('c.level = :level')
            ->andWhere('cd.containsNewsProduct = true')
            ->setParameter('level', CategoryFacade::NEWS_CATEGORIES_LEVEL);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @return string
     */
    public function getSaleCategoriesHash(): string
    {
        $hashBase = '';
        $saleCategories = $this->getAllQueryBuilder()
            ->select('c.id, cd.domainId')
            ->join(CategoryDomain::class, 'cd', Join::WITH, 'cd.category = c.id')
            ->andWhere('c.level = :level')
            ->andWhere('cd.containsSaleProduct = true')
            ->setParameter('level', CategoryFacade::SALE_CATEGORIES_LEVEL)
            ->orderBy('c.id, cd.domainId')
            ->getQuery()->execute();

        foreach ($saleCategories as $saleCategory) {
            $hashBase .= $saleCategory['id'] . $saleCategory['domainId'];
        }

        return md5($hashBase);
    }

    public function markSaleCategories(): void
    {
        $now = new DateTime();

        $query = $this->em->createNativeQuery(
            'UPDATE
                category_domains AS cd
                SET
                contains_sale_product = CASE
                    WHEN (
                        EXISTS(
                            SELECT pcd.category_id
                            FROM product_category_domains pcd
                            INNER JOIN product_flags pf ON pf.product_id = pcd.product_id
                            INNER JOIN flags f ON pf.flag_id = f.id
                            INNER JOIN product_visibilities pv ON pv.product_id = pcd.product_id AND pv.domain_id = pcd.domain_id
                            INNER JOIN pricing_groups pg ON pg.id = pv.pricing_group_id AND pg.domain_id = pcd.domain_id
                            WHERE f.pohoda_id IN (:saleFlagPohodaIds)
                                AND (pf.active_from IS NULL OR pf.active_from <= :now)
                                AND (pf.active_to IS NULL OR pf.active_to >= :now)
                                AND pv.visible = true
                                AND pg.internal_id = \'ordinary_customer\'
                                AND pcd.domain_id = cd.domain_id
                                AND pcd.category_id = cd.category_id
                            GROUP BY pcd.category_id
                        )
                    )
                    THEN TRUE
                    ELSE FALSE
                END',
            new ResultSetMapping()
        );

        $query->execute([
            'now' => $now,
            'saleFlagPohodaIds' => [Flag::POHODA_ID_DISCOUNT, Flag::POHODA_ID_CLEARANCE],
        ]);
    }

    /**
     * @return string
     */
    public function getNewsCategoriesHash(): string
    {
        $hashBase = '';
        $newsCategories = $this->getAllQueryBuilder()
            ->select('c.id, cd.domainId')
            ->join(CategoryDomain::class, 'cd', Join::WITH, 'cd.category = c.id')
            ->andWhere('c.level = :level')
            ->andWhere('cd.containsNewsProduct = true')
            ->setParameter('level', CategoryFacade::NEWS_CATEGORIES_LEVEL)
            ->orderBy('c.id, cd.domainId')
            ->getQuery()->execute();

        foreach ($newsCategories as $newsCategory) {
            $hashBase .= $newsCategory['id'] . $newsCategory['domainId'];
        }

        return md5($hashBase);
    }

    public function markNewsCategories(): void
    {
        $now = new DateTime();

        $query = $this->em->createNativeQuery(
            'UPDATE
                category_domains AS cd
                SET
                contains_news_product = CASE
                    WHEN (
                        EXISTS(
                            SELECT pcd.category_id
                            FROM product_category_domains pcd
                            INNER JOIN product_flags pf ON pf.product_id = pcd.product_id
                            INNER JOIN flags f ON pf.flag_id = f.id
                            INNER JOIN product_visibilities pv ON pv.product_id = pcd.product_id AND pv.domain_id = pcd.domain_id
                            INNER JOIN pricing_groups pg ON pg.id = pv.pricing_group_id AND pg.domain_id = pcd.domain_id
                            WHERE f.pohoda_id = :newsFlagPohodaId
                                AND (pf.active_from IS NULL OR pf.active_from <= :now)
                                AND (pf.active_to IS NULL OR pf.active_to >= :now)
                                AND pv.visible = true
                                AND pg.internal_id = \'ordinary_customer\'
                                AND pcd.domain_id = cd.domain_id
                                AND pcd.category_id = cd.category_id
                            GROUP BY pcd.category_id
                        )
                    )
                    THEN TRUE
                    ELSE FALSE
                END',
            new ResultSetMapping()
        );

        $query->execute([
            'now' => $now,
            'newsFlagPohodaId' => Flag::POHODA_ID_NEW,
        ]);
    }

    /**
     * @param \App\Model\Product\Brand\Brand $brand
     * @param int $level
     * @param int $domainId
     * @return \App\Model\Category\Category[]
     */
    public function getAllVisibleCategoriesByBrandLevelAndDomain(Brand $brand, int $level, int $domainId): array
    {
        $queryBuilder = $this->getAllVisibleAndListableByDomainIdQueryBuilder($domainId)
            ->join(
                ProductCategoryDomain::class,
                'pcd',
                Join::WITH,
                'pcd.category = c
                    AND pcd.domainId = :domainId'
            )
            ->join(
                Product::class,
                'pr',
                Join::WITH,
                'pr.brand = :brand
                    AND pr = pcd.product'
            )
            ->join(
                ProductVisibility::class,
                'pv',
                Join::WITH,
                'pv.domainId = :domainId
                    AND pv.product = pcd.product'
            )
            ->select('c')
            ->andWhere('c.listable = true')
            ->andWhere('c.level = :categoryLevel')
            ->andWhere('pv.visible = false')
            ->andWhere('pr.calculatedSellingDenied = false')
            ->setParameter('domainId', $domainId)
            ->setParameter('brand', $brand)
            ->setParameter('categoryLevel', $level)
            ->orderBy('c.lft');

        return $queryBuilder->getQuery()->getResult();
    }
}
