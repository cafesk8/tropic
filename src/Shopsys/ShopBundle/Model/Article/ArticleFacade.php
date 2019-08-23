<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Article;

use Shopsys\FrameworkBundle\Model\Article\ArticleFacade as BaseArticleFacade;

class ArticleFacade extends BaseArticleFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Article\ArticleRepository
     */
    protected $articleRepository;

    /**
     * @param string $placement
     * @param int $limit
     * @return \Shopsys\ShopBundle\Model\Article\Article[]
     */
    public function getVisibleArticlesOnCurrentDomainByPlacementAndLimit(string $placement, int $limit): array
    {
        return $this->articleRepository->getVisibleArticlesOnCurrentDomainByPlacementAndLimit($this->domain->getId(), $placement, $limit);
    }
}
