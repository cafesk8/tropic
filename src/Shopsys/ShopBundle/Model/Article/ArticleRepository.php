<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Article;

use Shopsys\FrameworkBundle\Model\Article\ArticleRepository as BaseArticleRepository;

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
