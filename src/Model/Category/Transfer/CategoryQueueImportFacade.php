<?php

declare(strict_types=1);

namespace App\Model\Category\Transfer;

class CategoryQueueImportFacade
{
    /**
     * @var \App\Model\Category\Transfer\CategoryQueueImportRepository
     */
    private $categoryQueueImportRepository;

    /**
     * @param \App\Model\Category\Transfer\CategoryQueueImportRepository $categoryQueueImportRepository
     */
    public function __construct(CategoryQueueImportRepository $categoryQueueImportRepository)
    {
        $this->categoryQueueImportRepository = $categoryQueueImportRepository;
    }

    /**
     * @param array $pohodaCategoryIds
     * @param \DateTime $pohodaTransferDateTime
     */
    public function insertChangedPohodaCategoryIds(array $pohodaCategoryIds, \DateTime $pohodaTransferDateTime): void
    {
        $this->categoryQueueImportRepository->insertChangedPohodaCategoryIds($pohodaCategoryIds, $pohodaTransferDateTime);
    }

    /**
     * @param int $limit
     * @return int[]
     */
    public function findChangedPohodaCategoryIds(int $limit): array
    {
        return $this->categoryQueueImportRepository->findChangedPohodaCategoryIds($limit);
    }

    /**
     * @param array $updatedPohodaCategoryIds
     */
    public function removeUpdatedCategories(array $updatedPohodaCategoryIds): void
    {
        $this->categoryQueueImportRepository->removeUpdatedCategories($updatedPohodaCategoryIds);
    }

    /**
     * @return bool
     */
    public function isQueueEmpty(): bool
    {
        return $this->categoryQueueImportRepository->isQueueEmpty();
    }
}
