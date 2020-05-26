<?php

declare(strict_types=1);

namespace App\Model\Category\Transfer;

use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Component\Transfer\Pohoda\Category\PohodaCategory;
use App\Component\Transfer\Pohoda\Category\PohodaCategoryExportFacade;
use App\Model\Category\Category;
use App\Model\Category\CategoryDataFactory;
use App\Model\Category\CategoryFacade;

class CategoryImportFacade
{
    public const MAX_BATCH_LIMIT = 1000;

    /**
     * @var \App\Component\Transfer\Pohoda\Category\PohodaCategoryExportFacade
     */
    private $pohodaCategoryExportFacade;

    /**
     * @var \App\Model\Category\Transfer\CategoryQueueImportFacade
     */
    private $categoryQueueImportFacade;

    /**
     * @var \App\Component\Transfer\Logger\TransferLogger
     */
    private $logger;

    /**
     * @var \App\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \App\Model\Category\CategoryDataFactory
     */
    private $categoryDataFactory;

    /**
     * @var \App\Model\Category\Transfer\PohodaCategoryMapper
     */
    private $pohodaCategoryMapper;

    /**
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Component\Transfer\Pohoda\Category\PohodaCategoryExportFacade $pohodaCategoryExportFacade
     * @param \App\Model\Category\Transfer\CategoryQueueImportFacade $categoryQueueImportFacade
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Model\Category\CategoryDataFactory $categoryDataFactory
     * @param \App\Model\Category\Transfer\PohodaCategoryMapper $pohodaCategoryMapper
     */
    public function __construct(
        TransferLoggerFactory $transferLoggerFactory,
        PohodaCategoryExportFacade $pohodaCategoryExportFacade,
        CategoryQueueImportFacade $categoryQueueImportFacade,
        CategoryFacade $categoryFacade,
        CategoryDataFactory $categoryDataFactory,
        PohodaCategoryMapper $pohodaCategoryMapper
    ) {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(CategoryImportCronModule::TRANSFER_IDENTIFIER);
        $this->pohodaCategoryExportFacade = $pohodaCategoryExportFacade;
        $this->categoryQueueImportFacade = $categoryQueueImportFacade;
        $this->categoryFacade = $categoryFacade;
        $this->categoryDataFactory = $categoryDataFactory;
        $this->pohodaCategoryMapper = $pohodaCategoryMapper;
    }

    public function processImport(): void
    {
        $changedPohodaCategoryIds = $this->categoryQueueImportFacade->findChangedPohodaCategoryIds(self::MAX_BATCH_LIMIT);
        $pohodaCategories = $this->pohodaCategoryExportFacade->getPohodaCategoriesByPohodaCategoryIds(
            $changedPohodaCategoryIds
        );
        if (count($pohodaCategories) === 0) {
            $this->logger->addInfo('Žádné kategorie k importu z fronty');
        } else {
            $this->logger->addInfo('Proběhne uložení kategorií', ['pohodaCategoriesCount' => count($pohodaCategories)]);
            $updatedPohodaCategoryIds = $this->updateCategoriesByPohodaCategories($pohodaCategories);
            $this->categoryQueueImportFacade->removeUpdatedCategories($updatedPohodaCategoryIds);

            $categoriesForOrderRecalculation = $this->categoryFacade->getCategoriesForOrderRecalculation();
            $this->logger->addInfo('Proběhne přepočet kategorií', ['countCategoriesForOrderRecalculation' => count($categoriesForOrderRecalculation)]);
            $this->categoryFacade->editOrdering($categoriesForOrderRecalculation);
        }
        $this->logger->persistTransferIssues();
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Category\PohodaCategory[] $pohodaCategories
     * @return int[]
     */
    private function updateCategoriesByPohodaCategories(array $pohodaCategories): array
    {
        $updatedPohodaCategoryIds = [];

        foreach ($pohodaCategories as $pohodaCategory) {
            $category = $this->categoryFacade->findByPohodaId($pohodaCategory->pohodaId);
            if ($category !== null) {
                $updatedPohodaCategoryIds[] = $this->editCategoryByPohodaCategory($category, $pohodaCategory);
            } else {
                $updatedPohodaCategoryIds[] = $this->createCategoryByPohodaCategory($pohodaCategory);
            }
        }

        return $updatedPohodaCategoryIds;
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Category\PohodaCategory $pohodaCategory
     * @return int
     */
    private function createCategoryByPohodaCategory(PohodaCategory $pohodaCategory): int
    {
        $categoryData = $this->categoryDataFactory->create();
        try {
            $this->pohodaCategoryMapper->mapPohodaCategoryToCategoryData($pohodaCategory, $categoryData);
        } catch (\Exception $exc) {
            $this->logger->addError('Vytvoření kategorie selhalo', [
                'pohodaId' => $pohodaCategory->pohodaId,
                'categoryName' => $pohodaCategory->name,
                'exceptionMessage' => $exc->getMessage(),
            ]);
        }
        $createdCategory = $this->categoryFacade->create($categoryData);

        return $createdCategory->getPohodaId();
    }

    /**
     * @param \App\Model\Category\Category $category
     * @param \App\Component\Transfer\Pohoda\Category\PohodaCategory $pohodaCategory
     * @return int
     */
    private function editCategoryByPohodaCategory(Category $category, PohodaCategory $pohodaCategory): int
    {
        $categoryData = $this->categoryDataFactory->createFromCategory($category);
        try {
            $this->pohodaCategoryMapper->mapPohodaCategoryToCategoryData($pohodaCategory, $categoryData);
        } catch (\Exception $exc) {
            $this->logger->addError('Editace položky selhala.', [
                'categoryId' => $category->getId(),
                'categoryName' => $categoryData->name,
                'exceptionMessage' => $exc->getMessage(),
            ]);
        }
        $editedCategory = $this->categoryFacade->edit($category->getId(), $categoryData);
        $this->logger->addInfo('Kategorie upravena', [
            'pohodaId' => $editedCategory->getPohodaId(),
            'categoryId' => $editedCategory->getId(),
        ]);

        return $editedCategory->getPohodaId();
    }
}
