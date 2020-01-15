<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Category;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\Paginator\PaginationResult;
use Shopsys\FrameworkBundle\Component\Paginator\QueryPaginator;
use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Category\CategoryDomain;
use Shopsys\FrameworkBundle\Model\Category\CategoryRepository as BaseCategoryRepository;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomain;

class CategoryRepository extends BaseCategoryRepository
{
    /**
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Category\Category[]
     */
    public function getAllVisibleCategoriesForFirstColumnByDomainId(int $domainId): array
    {
        return $this->getAllVisibleAndListableByDomainIdQueryBuilder($domainId)
            ->andWhere('c.displayedInFirstColumn = TRUE')
            ->getQuery()
            ->execute();
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    private function getProductCategoryDomainRepository(): EntityRepository
    {
        return $this->em->getRepository(ProductCategoryDomain::class);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param int $domainId
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getProductVisibleAndListableProductCategoryDomainsQueryBuilder(Product $product, int $domainId)
    {
        return $this->getProductCategoryDomainRepository()->createQueryBuilder('pcd')
            ->select('pcd')
            ->innerJoin('pcd.category', 'c')
            ->innerJoin('c.domains', 'cd')
            ->andWhere('pcd.product = :product')
            ->andWhere('pcd.domainId = :domainId')
            ->andWhere('cd.domainId = :domainId')
            ->andWhere('cd.visible = true')
            ->andWhere('c.listable = true')
            ->andWhere('c.parent IS NOT NULL')
            ->andWhere('cd.enabled = true')
            ->setParameter('product', $product)
            ->setParameter('domainId', $domainId);
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
     * @return \Shopsys\ShopBundle\Model\Category\Category[]
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
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomain[]
     */
    public function getProductVisibleAndListableProductCategoryDomains(Product $product, int $domainId): array
    {
        return $this->getProductVisibleAndListableProductCategoryDomainsQueryBuilder($product, $domainId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $domainId
     * @return int|null
     */
    public function getHighestLegendaryCategoryIdByDomainId(int $domainId): ?int
    {
        /** @var \Shopsys\ShopBundle\Model\Category\Category|null $highestCategory */
        $highestCategory = $this
            ->getAllVisibleByDomainIdQueryBuilder($domainId)
            ->andWhere('c.legendaryCategory = TRUE')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($highestCategory !== null) {
            return $highestCategory->getId();
        }

        return null;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
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
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
     * @param int $domainId
     * @return array
     */
    public function getAllVisibleAndListableChildrenByCategoryAndDomainId(Category $category, int $domainId): array
    {
        $queryBuilder = $this->getAllVisibleAndListableByDomainIdQueryBuilder($domainId)
            ->andWhere('c.parent = :category')
            ->setParameter('category', $category);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Category\Category $parentCategory
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \Shopsys\ShopBundle\Model\Category\Category[]
     */
    public function getTranslatedVisibleAndListableSubcategoriesByDomain(Category $parentCategory, DomainConfig $domainConfig): array
    {
        $queryBuilder = $this->getAllVisibleAndListableByDomainIdQueryBuilder($domainConfig->getId());
        $this->addTranslation($queryBuilder, $domainConfig->getLocale());

        $queryBuilder
            ->andWhere('c.parent = :parentCategory')
            ->setParameter('parentCategory', $parentCategory);

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Category\Category[] $categories
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Category\Category[]
     */
    public function getCategoriesWithVisibleAndListableChildren(array $categories, int $domainId): array
    {
        $queryBuilder = $this->getAllVisibleAndListableByDomainIdQueryBuilder($domainId);

        $queryBuilder
            ->join(Category::class, 'cc', Join::WITH, 'cc.parent = c')
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
}
