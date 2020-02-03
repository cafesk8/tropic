<?php

declare(strict_types=1);

namespace App\Model\Category;

use Shopsys\FrameworkBundle\Component\Domain\Domain;

class HorizontalCategoryFacade
{
    /**
     * @var \App\Model\Category\HorizontalCategoryRepository
     */
    private $horizontalCategoryRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \App\Model\Category\HorizontalCategoryRepository $horizontalCategoryRepository
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        HorizontalCategoryRepository $horizontalCategoryRepository,
        Domain $domain
    ) {
        $this->horizontalCategoryRepository = $horizontalCategoryRepository;
        $this->domain = $domain;
    }

    /**
     * @return \App\Model\Category\Category[]
     */
    public function getCategoriesForHorizontalMenuOnCurrentDomain(): array
    {
        $domainId = $this->domain->getId();

        return $this->horizontalCategoryRepository->getCategoriesForHorizontalMenu($domainId);
    }
}
