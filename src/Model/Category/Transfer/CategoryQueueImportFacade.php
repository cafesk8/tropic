<?php

declare(strict_types=1);

namespace App\Model\Category\Transfer;

use App\Component\Transfer\Logger\TransferLoggerFactory;
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
     * @var \App\Component\Transfer\Logger\TransferLogger
     */
    private $logger;

    /**
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Model\Category\Transfer\CategoryQueueImportRepository $categoryQueueImportRepository
     * @param \App\Component\Transfer\Pohoda\Category\PohodaCategoryExportFacade $pohodaCategoryExportFacade
     */
    public function __construct(
        TransferLoggerFactory $transferLoggerFactory,
        CategoryQueueImportRepository $categoryQueueImportRepository,
        PohodaCategoryExportFacade $pohodaCategoryExportFacade
    ) {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(CategoryImportCronModule::TRANSFER_IDENTIFIER);
        $this->categoryQueueImportRepository = $categoryQueueImportRepository;
        $this->pohodaCategoryExportFacade = $pohodaCategoryExportFacade;
    }

    /**
     * @param \DateTime $dateTimeBeforeTransferFromPohodaServer
     * @param \DateTime|null $lastModificationDate
     */
    public function importDataToQueue(
        \DateTime $dateTimeBeforeTransferFromPohodaServer,
        ?\DateTime $lastModificationDate
    ): void {
        $pohodaCategoryIds = $this->pohodaCategoryExportFacade->getPohodaCategoryIdsByLastUpdateTime($lastModificationDate);
        if (count($pohodaCategoryIds) === 0) {
            $this->logger->addInfo('Žádné kategorie k importu do fronty');
        } else {
            $this->insertChangedPohodaCategoryIds($pohodaCategoryIds, $dateTimeBeforeTransferFromPohodaServer);
            $this->logger->addInfo('Proběhlo vložení změněných kategorií do fronty', ['pohodaCategoryIdsCount' => count($pohodaCategoryIds)]);
        }
        $this->logger->persistTransferIssues();
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
