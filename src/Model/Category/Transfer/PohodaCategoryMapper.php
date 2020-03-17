<?php

declare(strict_types=1);

namespace App\Model\Category\Transfer;

use App\Component\Domain\DomainHelper;
use App\Component\Transfer\Pohoda\Category\PohodaCategory;
use App\Model\Category\CategoryData;
use Shopsys\FrameworkBundle\Component\Domain\Domain;

class PohodaCategoryMapper
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(Domain $domain)
    {
        $this->domain = $domain;
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Category\PohodaCategory $pohodaCategory
     * @param \App\Model\Category\CategoryData $categoryData
     */
    public function mapPohodaCategoryToCategoryData(
        PohodaCategory $pohodaCategory,
        CategoryData $categoryData
    ): void {
        $categoryData->pohodaId = $pohodaCategory->pohodaId;
        $categoryData->name[DomainHelper::CZECH_LOCALE] = $pohodaCategory->name;
        $categoryData->name[DomainHelper::SLOVAK_LOCALE] = $pohodaCategory->nameSk;
        foreach ($this->domain->getAllIds() as $domainId) {
            $categoryData->enabled[$domainId] = $pohodaCategory->listable;
        }
        $categoryData->updatedByPohodaAt = new \DateTime();
        $categoryData->pohodaParentId = $pohodaCategory->parentId;
        $categoryData->pohodaPosition = $pohodaCategory->position;
    }
}
