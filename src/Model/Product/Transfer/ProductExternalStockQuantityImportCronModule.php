<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Transfer\AbstractTransferCronModule;
use App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager;
use App\Component\Transfer\TransferCronModuleDependency;

class ProductExternalStockQuantityImportCronModule extends AbstractTransferCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_products_external_stock_quantity';

    private const MAXIMUM_PRODUCTS_UPDATE = 40000;

    private PohodaEntityManager $pohodaEntityManager;

    private ProductExternalStockQuantityQueueImportFacade $productExternalStockQuantityQueueImportFacade;

    private ProductExternalStockQuantityImportFacade $productStockQuantityImportFacade;

    private int $totalUpdatedProducts = 0;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Model\Product\Transfer\ProductExternalStockQuantityImportFacade $productStockQuantityImportFacade
     * @param \App\Model\Product\Transfer\ProductExternalStockQuantityQueueImportFacade $productExternalStockQuantityQueueImportFacade
     * @param \App\Component\Transfer\Pohoda\Doctrine\PohodaEntityManager $pohodaEntityManager
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        ProductExternalStockQuantityImportFacade $productStockQuantityImportFacade,
        ProductExternalStockQuantityQueueImportFacade $productExternalStockQuantityQueueImportFacade,
        PohodaEntityManager $pohodaEntityManager
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->pohodaEntityManager = $pohodaEntityManager;
        $this->productExternalStockQuantityQueueImportFacade = $productExternalStockQuantityQueueImportFacade;
        $this->productStockQuantityImportFacade = $productStockQuantityImportFacade;
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
        $this->productExternalStockQuantityQueueImportFacade->importDataToQueue($dateTimeBeforeTransferFromPohodaServer, $transfer->getLastStartAt());

        $updatedProductIds = $this->productStockQuantityImportFacade->processImport();
        $this->totalUpdatedProducts += count($updatedProductIds);

        return $this->totalUpdatedProducts <= self::MAXIMUM_PRODUCTS_UPDATE && !$this->productExternalStockQuantityQueueImportFacade->isQueueEmpty();
    }
}
