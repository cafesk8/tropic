<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Transfer\AbstractTransferCronModule;
use App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager;
use App\Component\Transfer\TransferCronModuleDependency;
use App\Model\Product\ProductVisibilityRepository;
use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportSubscriber;

class ProductImportCronModule extends AbstractTransferCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_products';

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportSubscriber
     */
    private $productExportSubscriber;

    /**
     * @var \App\Model\Product\Transfer\ProductImportFacade
     */
    private $productImportFacade;

    /**
     * @var \App\Model\Product\Transfer\ProductInfoQueueImportFacade
     */
    private $productInfoQueueImportFacade;

    /**
     * @var \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager
     */
    private $pohodaEntityManager;

    /**
     * @var \App\Model\Product\ProductVisibilityRepository
     */
    private $productVisibilityRepository;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Model\Product\Transfer\ProductImportFacade $productImportFacade
     * @param \App\Model\Product\Transfer\ProductInfoQueueImportFacade $productInfoQueueImportFacade
     * @param \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager $pohodaEntityManager
     * @param \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportSubscriber $productExportSubscriber
     * @param \App\Model\Product\ProductVisibilityRepository $productVisibilityRepository
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        ProductImportFacade $productImportFacade,
        ProductInfoQueueImportFacade $productInfoQueueImportFacade,
        PohodaEntityManager $pohodaEntityManager,
        ProductExportSubscriber $productExportSubscriber,
        ProductVisibilityRepository $productVisibilityRepository
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->productImportFacade = $productImportFacade;
        $this->productInfoQueueImportFacade = $productInfoQueueImportFacade;
        $this->pohodaEntityManager = $pohodaEntityManager;
        $this->productExportSubscriber = $productExportSubscriber;
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
        $transfer = $this->transferFacade->getByIdentifier(self::TRANSFER_IDENTIFIER);
        $dateTimeBeforeTransferFromPohodaServer = $this->pohodaEntityManager->getCurrentDateTimeFromPohodaDatabase();
        $this->productInfoQueueImportFacade->importDataToQueue($dateTimeBeforeTransferFromPohodaServer, $transfer->getLastStartAt());

        $changedPohodaProductIds = $this->productImportFacade->processImport();
        $this->runRecalculations($changedPohodaProductIds);

        return false;
    }

    /**
     * @param array $changedPohodaProductIds
     */
    protected function runRecalculations(array $changedPohodaProductIds): void
    {
        if (count($changedPohodaProductIds) > 0) {
            $this->productVisibilityRepository->refreshProductsVisibility(true);
            $this->productExportSubscriber->exportScheduledRows();
        }
    }
}
