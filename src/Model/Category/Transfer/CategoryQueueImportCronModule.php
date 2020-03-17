<?php

declare(strict_types=1);

namespace App\Model\Category\Transfer;

use App\Component\Transfer\AbstractTransferCronModule;
use App\Component\Transfer\Pohoda\Category\PohodaCategoryExportFacade;
use App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager;
use App\Component\Transfer\TransferCronModuleDependency;

class CategoryQueueImportCronModule extends AbstractTransferCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_changed_category_ids';

    /**
     * @var \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager
     */
    private $pohodaEntityManager;

    /**
     * @var \App\Component\Transfer\Pohoda\Category\PohodaCategoryExportFacade
     */
    private $pohodaCategoryExportFacade;

    /**
     * @var \App\Model\Category\Transfer\CategoryQueueImportFacade
     */
    private $categoryQueueImportFacade;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Component\Transfer\Pohoda\Category\PohodaCategoryExportFacade $pohodaCategoryExportFacade
     * @param \App\Model\Category\Transfer\CategoryQueueImportFacade $categoryQueueImportFacade
     * @param \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager $pohodaEntityManager
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        PohodaCategoryExportFacade $pohodaCategoryExportFacade,
        CategoryQueueImportFacade $categoryQueueImportFacade,
        PohodaEntityManager $pohodaEntityManager
    ) {
        parent::__construct($transferCronModuleDependency);

        $this->pohodaEntityManager = $pohodaEntityManager;
        $this->pohodaCategoryExportFacade = $pohodaCategoryExportFacade;
        $this->categoryQueueImportFacade = $categoryQueueImportFacade;
    }

    /**
     * @inheritDoc
     */
    protected function getTransferIdentifier(): string
    {
        return self::TRANSFER_IDENTIFIER;
    }

    /**
     * @inheritDoc
     */
    protected function runTransfer(): bool
    {
        $transfer = $this->transferFacade->getByIdentifier(self::TRANSFER_IDENTIFIER);
        $dateTimeBeforeTransferFromPohodaServer = $this->pohodaEntityManager->getCurrentDateTimeFromPohodaDatabase();

        $pohodaCategoryIds = $this->pohodaCategoryExportFacade->getPohodaCategoryIdsByLastUpdateTime($transfer->getLastStartAt());
        if (count($pohodaCategoryIds) === 0) {
            $this->logger->addInfo('Nejsou žádná data ke zpracování');
        } else {
            $this->categoryQueueImportFacade->insertChangedPohodaCategoryIds($pohodaCategoryIds, $dateTimeBeforeTransferFromPohodaServer);
            $this->logger->addInfo('Celkem změněných kategorií', ['pohodaCategoryIdsCount' => count($pohodaCategoryIds)]);
        }

        return false;
    }
}
