<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Category;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
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
        return $this->getAllVisibleByDomainIdQueryBuilder($domainId)
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
    private function getProductVisibleProductCategoryDomainsQueryBuilder(Product $product, int $domainId)
    {
        return $this->getProductCategoryDomainRepository()->createQueryBuilder('pcd')
            ->select('pcd')
            ->innerJoin('pcd.category', 'c')
            ->innerJoin('c.domains', 'cd')
            ->andWhere('pcd.product = :product')
            ->andWhere('pcd.domainId = :domainId')
            ->andWhere('cd.domainId = :domainId')
            ->andWhere('cd.visible = true')
            ->andWhere('cd.enabled = true')
            ->setParameter('product', $product)
            ->setParameter('domainId', $domainId);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomain[]
     */
    public function getProductVisibleProductCategoryDomains(Product $product, int $domainId): array
    {
        return $this->getProductVisibleProductCategoryDomainsQueryBuilder($product, $domainId)
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
}
