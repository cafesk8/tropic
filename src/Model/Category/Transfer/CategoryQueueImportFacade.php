<?php

declare(strict_types=1);

namespace App\Model\Category\Transfer;

use App\Component\Transfer\Logger\TransferLogger;
use App\Component\Transfer\Pohoda\Category\PohodaCategoryExportFacade;

class CategoryQueueImportFacade
{
    /**
     * @var \App\Component\Transfer\Pohoda\Category\PohodaCategoryExportFacade
     */
    protected $pohodaCategoryExportFacade;

    /**
     * @var \App\Model\Category\Transfer\CategoryQueueImportRepository
     */
    private $categoryQueueImportRepository;

    /**
     * @param \App\Model\Category\Transfer\CategoryQueueImportRepository $categoryQueueImportRepository
     * @param \App\Component\Transfer\Pohoda\Category\PohodaCategoryExportFacade $pohodaCategoryExportFacade
     */
    public function __construct(
        CategoryQueueImportRepository $categoryQueueImportRepository,
        PohodaCategoryExportFacade $pohodaCategoryExportFacade
    ) {
        $this->categoryQueueImportRepository = $categoryQueueImportRepository;
        $this->pohodaCategoryExportFacade = $pohodaCategoryExportFacade;
    }

    /**
     * @param \App\Component\Transfer\Logger\TransferLogger $transferLogger
     * @param \DateTime $dateTimeBeforeTransferFromPohodaServer
     * @param \DateTime|null $lastModificationDate
     */
    public function importDataToQueue(
        TransferLogger $transferLogger,
        \DateTime $dateTimeBeforeTransferFromPohodaServer,
        ?\DateTime $lastModificationDate
    ) {
        $pohodaCategoryIds = $this->pohodaCategoryExportFacade->getPohodaCategoryIdsByLastUpdateTime($lastModificationDate);
        if (count($pohodaCategoryIds) === 0) {
            $transferLogger->addInfo('Nejsou žádná data ke zpracování');
        } else {
            $this->insertChangedPohodaCategoryIds($pohodaCategoryIds, $dateTimeBeforeTransferFromPohodaServer);
            $transferLogger->addInfo('Celkem změněných kategorií', ['pohodaCategoryIdsCount' => count($pohodaCategoryIds)]);
        }
    }

    /**
     * @param array $pohodaCategoryIds
     * @param \DateTime $pohodaTransferDateTime
     */
    private function insertChangedPohodaCategoryIds(array $pohodaCategoryIds, \DateTime $pohodaTransferDateTime): void
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
