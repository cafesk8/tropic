<?php

declare(strict_types=1);

namespace App\Model\Category;

use Shopsys\FrameworkBundle\Model\Category\CategoryRepository;

class HorizontalCategoryRepository
{
    /**
     * @var \App\Model\Category\CategoryRepository
     */
    private $categoryRepository;

    /**
     * @param \App\Model\Category\CategoryRepository $categoryRepository
     */
    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param int $domainId
     * @return \App\Model\Category\Category[]
     */
    public function getCategoriesForHorizontalMenu(int $domainId): array
    {
        $queryBuilder = $this->categoryRepository->getAllVisibleAndListableByDomainIdQueryBuilder($domainId);
        $queryBuilder->andWhere('c.displayedInHorizontalMenu = TRUE');

        return $queryBuilder->getQuery()->execute();
    }
}
