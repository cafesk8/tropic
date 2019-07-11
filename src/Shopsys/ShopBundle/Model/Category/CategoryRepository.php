<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Category;

use Shopsys\FrameworkBundle\Model\Category\CategoryRepository as BaseCategoryRepository;

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
}
