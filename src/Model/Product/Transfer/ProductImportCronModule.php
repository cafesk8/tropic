<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Transfer\AbstractTransferCronModule;
use App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager;
use App\Component\Transfer\TransferCronModuleDependency;
use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportSubscriber;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade;

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

    private ProductVisibilityFacade $productVisibilityFacade;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Model\Product\Transfer\ProductImportFacade $productImportFacade
     * @param \App\Model\Product\Transfer\ProductInfoQueueImportFacade $productInfoQueueImportFacade
     * @param \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager $pohodaEntityManager
     * @param \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportSubscriber $productExportSubscriber
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade $productVisibilityFacade
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        ProductImportFacade $productImportFacade,
        ProductInfoQueueImportFacade $productInfoQueueImportFacade,
        PohodaEntityManager $pohodaEntityManager,
        ProductExportSubscriber $productExportSubscriber,
        ProductVisibilityFacade $productVisibilityFacade
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->productImportFacade = $productImportFacade;
        $this->productInfoQueueImportFacade = $productInfoQueueImportFacade;
        $this->pohodaEntityManager = $pohodaEntityManager;
        $this->productExportSubscriber = $productExportSubscriber;
        $this->productVisibilityFacade = $productVisibilityFacade;
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
            $this->productVisibilityFacade->refreshProductsVisibilityForMarked();
            $this->productExportSubscriber->exportScheduledRows();
        }
    }
}
