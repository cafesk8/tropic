<?php

declare(strict_types=1);

namespace App\Model\Category\Transfer;

use App\Component\Transfer\AbstractTransferCronModule;
use App\Component\Transfer\Pohoda\Category\PohodaCategoryExportFacade;
use App\Component\Transfer\TransferCronModuleDependency;
use App\Model\Category\CategoryFacade;
use App\Model\Category\Transfer\Exception\MaximumPercentageOfCategoriesToRemoveLimitExceeded;
use Shopsys\FrameworkBundle\Model\Category\CategoryVisibilityRepository;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityRepository;

class CategoryRemoveCronModule extends AbstractTransferCronModule
{
    public const TRANSFER_IDENTIFIER = 'remove_categories';

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
     * @var \Shopsys\FrameworkBundle\Model\Category\CategoryVisibilityRepository
     */
    private $categoryVisibilityRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityRepository
     */
    private $productVisibilityRepository;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Component\Transfer\Pohoda\Category\PohodaCategoryExportFacade $pohodaCategoryExportFacade
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \Shopsys\FrameworkBundle\Model\Category\CategoryVisibilityRepository $categoryVisibilityRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityRepository $productVisibilityRepository
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        PohodaCategoryExportFacade $pohodaCategoryExportFacade,
        CategoryFacade $categoryFacade,
        CategoryVisibilityRepository $categoryVisibilityRepository,
        ProductVisibilityRepository $productVisibilityRepository
    ) {
        parent::__construct($transferCronModuleDependency);

        $this->pohodaCategoryExportFacade = $pohodaCategoryExportFacade;
        $this->categoryFacade = $categoryFacade;
        $this->categoryVisibilityRepository = $categoryVisibilityRepository;
        $this->productVisibilityRepository = $productVisibilityRepository;
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
        $allPohodaIds = $this->pohodaCategoryExportFacade->getAllPohodaIds();
        $this->logger->addInfo('Celkem kategorií z Pohody', ['allPohodaIdsCount' => count($allPohodaIds)]);

        try {
            $removedCategories = $this->categoryFacade->removeCategoriesExceptPohodaIds($allPohodaIds);

            if (count($removedCategories) > 0) {
                $this->logger->addInfo('Kategorie odstraněny', [
                    'removedCategoriesCount' => count($removedCategories),
                    'removedCategories' => $removedCategories,
                ]);

                $categoriesForOrderRecalculation = $this->categoryFacade->getCategoriesForOrderRecalculation();
                $this->logger->addInfo(
                    'Proběhne přepočet řazení kategorií',
                    ['countCategoriesForOrderRecalculation' => count($categoriesForOrderRecalculation)]
                );
                $this->categoryFacade->editOrdering($categoriesForOrderRecalculation);

                $this->logger->addInfo('Proběhne přepočet viditelnosti');
                $this->categoryVisibilityRepository->refreshCategoriesVisibility();
                $this->productVisibilityRepository->refreshProductsVisibility();
            } else {
                $this->logger->addInfo('Žádné kategorie k odstranění');
            }
        } catch (MaximumPercentageOfCategoriesToRemoveLimitExceeded $exception) {
            $this->logger->addError(
                'Přenos přerušen z důvodu možného smazání většiny stromu',
                ['exceptionMessage' => $exception->getMessage()]
            );
        }

        return false;
    }
}
