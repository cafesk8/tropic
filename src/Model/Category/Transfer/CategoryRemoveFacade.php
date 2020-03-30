<?php

declare(strict_types=1);

namespace App\Model\Category\Transfer;

use App\Component\Transfer\Logger\TransferLogger;
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
     * @param \App\Component\Transfer\Pohoda\Category\PohodaCategoryExportFacade $pohodaCategoryExportFacade
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     */
    public function __construct(
        PohodaCategoryExportFacade $pohodaCategoryExportFacade,
        CategoryFacade $categoryFacade
    ) {
        $this->pohodaCategoryExportFacade = $pohodaCategoryExportFacade;
        $this->categoryFacade = $categoryFacade;
    }

    /**
     * @param \App\Component\Transfer\Logger\TransferLogger $transferLogger
     */
    public function removeCategories(TransferLogger $transferLogger): void
    {
        $allPohodaIds = $this->pohodaCategoryExportFacade->getAllPohodaIds();
        $transferLogger->addInfo('Celkem kategorií z Pohody', ['allPohodaIdsCount' => count($allPohodaIds)]);

        try {
            $removedCategories = $this->categoryFacade->removeCategoriesExceptPohodaIds($allPohodaIds);

            if (count($removedCategories) > 0) {
                $transferLogger->addInfo('Kategorie odstraněny', [
                    'removedCategoriesCount' => count($removedCategories),
                    'removedCategories' => $removedCategories,
                ]);

                $categoriesForOrderRecalculation = $this->categoryFacade->getCategoriesForOrderRecalculation();
                $transferLogger->addInfo(
                    'Proběhne přepočet řazení kategorií',
                    ['countCategoriesForOrderRecalculation' => count($categoriesForOrderRecalculation)]
                );
                $this->categoryFacade->editOrdering($categoriesForOrderRecalculation);
            } else {
                $transferLogger->addInfo('Žádné kategorie k odstranění');
            }
        } catch (MaximumPercentageOfCategoriesToRemoveLimitExceeded $exception) {
            $transferLogger->addError(
                'Pokus o smazání kategorií přerušen z důvodu možného smazání většiny stromu',
                ['exceptionMessage' => $exception->getMessage()]
            );
        }
    }
}
