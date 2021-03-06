<?php

declare(strict_types=1);

namespace App\Model\Category\Transfer;

use App\Component\Redis\RedisFacade;
use App\Component\Transfer\AbstractTransferCronModule;
use App\Component\Transfer\Pohoda\Category\PohodaCategoryExportFacade;
use App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager;
use App\Component\Transfer\TransferCronModuleDependency;
use Shopsys\FrameworkBundle\Model\Category\CategoryVisibilityRepository;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityRepository;

class CategoryImportCronModule extends AbstractTransferCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_categories';

    /**
     * @var \App\Component\Transfer\Pohoda\Category\PohodaCategoryExportFacade
     */
    protected $pohodaCategoryExportFacade;

    /**
     * @var \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager
     */
    protected $pohodaEntityManager;

    /**
     * @var \App\Model\Category\Transfer\CategoryQueueImportFacade
     */
    protected $categoryQueueImportFacade;

    /**
     * @var \App\Model\Category\Transfer\CategoryRemoveFacade
     */
    protected $categoryRemoveFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Category\CategoryVisibilityRepository
     */
    protected $categoryVisibilityRepository;

    /**
     * @var \App\Model\Product\ProductVisibilityRepository
     */
    protected $productVisibilityRepository;

    /**
     * @var \App\Component\Redis\RedisFacade
     */
    protected $redisFacade;

    /**
     * @var \App\Model\Category\Transfer\CategoryImportFacade
     */
    private $categoryImportFacade;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Model\Category\Transfer\CategoryImportFacade $categoryImportFacade
     * @param \App\Component\Transfer\Pohoda\Category\PohodaCategoryExportFacade $pohodaCategoryExportFacade
     * @param \App\Model\Category\Transfer\CategoryQueueImportFacade $categoryQueueImportFacade
     * @param \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager $pohodaEntityManager
     * @param \App\Model\Category\Transfer\CategoryRemoveFacade $categoryRemoveFacade
     * @param \Shopsys\FrameworkBundle\Model\Category\CategoryVisibilityRepository $categoryVisibilityRepository
     * @param \App\Model\Product\ProductVisibilityRepository $productVisibilityRepository
     * @param \App\Component\Redis\RedisFacade $redisFacade
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        CategoryImportFacade $categoryImportFacade,
        PohodaCategoryExportFacade $pohodaCategoryExportFacade,
        CategoryQueueImportFacade $categoryQueueImportFacade,
        PohodaEntityManager $pohodaEntityManager,
        CategoryRemoveFacade $categoryRemoveFacade,
        CategoryVisibilityRepository $categoryVisibilityRepository,
        ProductVisibilityRepository $productVisibilityRepository,
        RedisFacade $redisFacade
    ) {
        parent::__construct($transferCronModuleDependency);

        $this->categoryImportFacade = $categoryImportFacade;
        $this->pohodaCategoryExportFacade = $pohodaCategoryExportFacade;
        $this->categoryQueueImportFacade = $categoryQueueImportFacade;
        $this->pohodaEntityManager = $pohodaEntityManager;
        $this->categoryRemoveFacade = $categoryRemoveFacade;
        $this->categoryVisibilityRepository = $categoryVisibilityRepository;
        $this->productVisibilityRepository = $productVisibilityRepository;
        $this->redisFacade = $redisFacade;
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

        $this->logger->addInfo('Prob??hne vlo??en?? kategori?? do fronty', [
            'changedCategoriesFrom' => $transfer->getLastStartAt(),
        ]);
        $this->categoryQueueImportFacade->importDataToQueue($dateTimeBeforeTransferFromPohodaServer, $transfer->getLastStartAt());

        $changedCategoriesCount = $this->categoryImportFacade->processImport();
        $changedCategoriesCount += $this->categoryRemoveFacade->removeCategories();

        $this->logger->addInfo('Zm??n??n??ch kategori?? importem nebo maz??n??m', [
            'changedCategoriesCount' => $changedCategoriesCount,
        ]);

        if ($changedCategoriesCount > 0) {
            $this->logger->addInfo('Prob??hne p??epo??et viditelnosti kategori??');
            $this->categoryVisibilityRepository->refreshCategoriesVisibility();

            $this->logger->addInfo('Prob??hne smaz??n?? cache kategori??');
            $this->redisFacade->clearCacheByPattern('twig:', 'categories');
        }

        $this->logger->persistTransferIssues();

        return !$this->categoryQueueImportFacade->isQueueEmpty();
    }
}
