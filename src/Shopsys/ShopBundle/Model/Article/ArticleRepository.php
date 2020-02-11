<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Article;

use Shopsys\FrameworkBundle\Model\Article\ArticleRepository as BaseArticleRepository;

/**
 * @method \Shopsys\ShopBundle\Model\Article\Article|null findById(string $articleId)
 * @method \Shopsys\ShopBundle\Model\Article\Article[] getVisibleArticlesForPlacement(int $domainId, string $placement)
 * @method \Shopsys\ShopBundle\Model\Article\Article getById(int $articleId)
 * @method \Shopsys\ShopBundle\Model\Article\Article getVisibleById(int $articleId)
 * @method \Shopsys\ShopBundle\Model\Article\Article[] getAllByDomainId(int $domainId)
 */
class ArticleRepository extends BaseArticleRepository
{
    /**
     * @param int $domainId
     * @param string $placement
     * @param int $limit
     * @return \Shopsys\ShopBundle\Model\Article\Article[]
     */
    public function getVisibleArticlesOnCurrentDomainByPlacementAndLimit(int $domainId, string $placement, int $limit): array
    {
        $queryBuilder = $this->getVisibleArticlesByDomainIdQueryBuilder($domainId)
            ->andWhere('a.placement = :placement')->setParameter('placement', $placement)
            ->orderBy('a.position, a.id')
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }
}
