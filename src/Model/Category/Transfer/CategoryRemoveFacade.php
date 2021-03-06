<?php

declare(strict_types=1);

namespace App\Model\Category\Transfer;

use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Component\Transfer\Pohoda\Category\PohodaCategoryExportFacade;
use App\Model\Category\CategoryFacade;
use App\Model\Category\Transfer\Exception\MaximumPercentageOfCategoriesToRemoveLimitExceeded;

class CategoryRemoveFacade
{
    public const MAX_BATCH_CATEGORIES_REMOVE_PERCENT = 85;

    /**
     * @var \App\Component\Transfer\Pohoda\Category\PohodaCategoryExportFacade
     */
    private $pohodaCategoryExportFacade;

    /**
     * @var \App\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \App\Component\Transfer\Logger\TransferLogger
     */
    private $logger;

    /**
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Component\Transfer\Pohoda\Category\PohodaCategoryExportFacade $pohodaCategoryExportFacade
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     */
    public function __construct(
        TransferLoggerFactory $transferLoggerFactory,
        PohodaCategoryExportFacade $pohodaCategoryExportFacade,
        CategoryFacade $categoryFacade
    ) {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(CategoryImportCronModule::TRANSFER_IDENTIFIER);
        $this->pohodaCategoryExportFacade = $pohodaCategoryExportFacade;
        $this->categoryFacade = $categoryFacade;
    }

    /**
     * @return int
     */
    public function removeCategories(): int
    {
        $allPohodaIds = $this->pohodaCategoryExportFacade->getAllPohodaIds();
        $this->logger->addInfo('Kontrola všech kategorií z Pohody pro porovnání stromu', ['allPohodaIdsCount' => count($allPohodaIds)]);
        $removedCategoriesCount = 0;
        try {
            $removedCategories = $this->categoryFacade->removeCategoriesExceptPohodaIds($allPohodaIds);
            $removedCategoriesCount = count($removedCategories);
            if ($removedCategoriesCount > 0) {
                $this->logger->addInfo('Kategorie odstraněny z e-shopu', [
                    'removedCategoriesCount' => $removedCategoriesCount,
                    'removedCategories' => $removedCategories,
                ]);

                $categoriesForOrderRecalculation = $this->categoryFacade->getCategoriesForOrderRecalculation();
                $this->logger->addInfo(
                    'Proběhne přepočet řazení kategorií',
                    ['countCategoriesForOrderRecalculation' => count($categoriesForOrderRecalculation)]
                );
                $this->categoryFacade->editOrdering($categoriesForOrderRecalculation);
            } else {
                $this->logger->addInfo('Žádné kategorie k odstranění z e-shopu');
            }
        } catch (MaximumPercentageOfCategoriesToRemoveLimitExceeded $exception) {
            $this->logger->addError(
                'Pokus o smazání kategorií přerušen z důvodu možného smazání většiny stromu',
                ['exceptionMessage' => $exception->getMessage()]
            );
        }
        $this->logger->persistTransferIssues();

        return $removedCategoriesCount;
    }
}
