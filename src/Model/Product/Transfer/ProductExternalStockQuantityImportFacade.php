<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Transfer\Logger\TransferLogger;
use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Component\Transfer\Pohoda\Product\PohodaProduct;
use App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade;
use App\Model\Product\ProductFacade;
use App\Model\Product\StoreStock\ProductStoreStockFacade;
use App\Model\Store\StoreFacade;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class ProductExternalStockQuantityImportFacade
{
    /**
     * 2100 is the maximum number of parameters in the query
     */
    public const PRODUCT_EXPORT_STOCK_QUANTITIES_MAX_BATCH_LIMIT = 2000;

    /**
     * @var int[]
     */
    private array $updatedPohodaProductIds = [];

    /**
     * @var int[]
     */
    private array $updatedProductIds = [];

    /**
     * @var int[]
     */
    private array $notFoundProductPohodaIdsInEshop = [];

    private EntityManagerInterface $entityManager;

    private TransferLogger $logger;

    private PohodaProductExportFacade $pohodaProductExportFacade;

    private ProductExternalStockQuantityQueueImportFacade $productExternalStockQuantityQueueImportFacade;

    private StoreFacade $storeFacade;

    private ProductStoreStockFacade $productStoreStockFacade;

    private ProductFacade $productFacade;

    /**
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade $pohodaProductExportFacade
     * @param \App\Model\Product\Transfer\ProductExternalStockQuantityQueueImportFacade $productStockQuantityQueueImportFacade
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \App\Model\Store\StoreFacade $storeFacade
     * @param \App\Model\Product\StoreStock\ProductStoreStockFacade $productStoreStockFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     */
    public function __construct(
        TransferLoggerFactory $transferLoggerFactory,
        PohodaProductExportFacade $pohodaProductExportFacade,
        ProductExternalStockQuantityQueueImportFacade $productStockQuantityQueueImportFacade,
        EntityManagerInterface $entityManager,
        StoreFacade $storeFacade,
        ProductStoreStockFacade $productStoreStockFacade,
        ProductFacade $productFacade
    ) {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(ProductExternalStockQuantityImportCronModule::TRANSFER_IDENTIFIER);
        $this->entityManager = $entityManager;
        $this->pohodaProductExportFacade = $pohodaProductExportFacade;
        $this->productExternalStockQuantityQueueImportFacade = $productStockQuantityQueueImportFacade;
        $this->storeFacade = $storeFacade;
        $this->productStoreStockFacade = $productStoreStockFacade;
        $this->productFacade = $productFacade;
    }

    /**
     * @return int[]
     */
    public function processImport(): array
    {
        $this->updatedPohodaProductIds = [];
        $this->updatedProductIds = [];
        $this->notFoundProductPohodaIdsInEshop = [];

        $changedPohodaProductIds = $this->productExternalStockQuantityQueueImportFacade->getChangedPohodaProductIds(self::PRODUCT_EXPORT_STOCK_QUANTITIES_MAX_BATCH_LIMIT);
        $stockQuantities = $this->pohodaProductExportFacade->getPohodaProductExternalStockQuantitiesByProductIds(
            $changedPohodaProductIds
        );
        try {
            if (count($stockQuantities) === 0) {
                $this->logger->addInfo('Nejsou žádná data ve frontě skladových zásob ke zpracování');
            } else {
                $this->logger->addInfo('Proběhne uložení skladových zásob z fronty', [
                    'pohodaProductsCount' => count($stockQuantities),
                ]);
                $this->updateProductsStockQuantities($stockQuantities);
            }
        } catch (Exception $exception) {
            $this->logger->addError('Import skladových zásob selhal', [
                'exceptionMessage' => $exception->getMessage(),
            ]);
        } finally {
            $this->updatedPohodaProductIds = array_filter($this->updatedPohodaProductIds);
            $this->updatedProductIds = array_filter($this->updatedProductIds);
            $this->logger->addInfo('Proběhne smazání produktů z fronty skladových zásob', [
                'updatedPohodaProductIdsCount' => count($this->updatedPohodaProductIds),
            ]);
            $this->productExternalStockQuantityQueueImportFacade->removeProductsFromQueue($changedPohodaProductIds);
            $this->markProductsForRecalculation($this->updatedProductIds);
            $this->logger->addInfo('Nenalezené produkty v e-shopu', [
                'count' => count($this->notFoundProductPohodaIdsInEshop),
                'notFoundProductPohodaIdsInEshop' => $this->notFoundProductPohodaIdsInEshop,
            ]);

            $this->logger->persistTransferIssues();
        }

        return $this->updatedProductIds;

    }

    /**
     * @param array $stockQuantities
     */
    private function updateProductsStockQuantities(array $stockQuantities): void
    {
        $productIdsIndexedByPohodaIds = $this->productFacade->getProductIdsIndexedByPohodaIds(array_column($stockQuantities, PohodaProduct::COL_POHODA_ID));
        $externalStock = $this->storeFacade->findExternalStock();
        if ($externalStock === null) {
            $this->logger->addError('Externí sklad v eshopu neexistuje. Import skladových zásob externího skladu nebude proveden!');
        }

        foreach ($stockQuantities as $stockQuantity) {
            $pohodaId = (int)$stockQuantity[PohodaProduct::COL_POHODA_ID];
            $externalStockQuantity = (int)$stockQuantity[PohodaProduct::COL_EXTERNAL_STOCK];
            try {
                $this->editProductQuantities($pohodaId, $productIdsIndexedByPohodaIds, $externalStockQuantity, $externalStock->getId());
            } catch (Exception $exc) {
                $this->logger->addError('Chyba importu skladových zásob externího skladu', [
                    'pohodaId' => $pohodaId,
                    'externalStockQuantity' => $externalStockQuantity,
                    'exceptionMessage' => $exc->getMessage(),
                ]);
            }

            $this->entityManager->clear();
        }
    }

    /**
     * @param int $pohodaId
     * @param array $productIdsIndexedByPohodaIds
     * @param int $externalStockQuantity
     * @param int $externalStockId
     */
    private function editProductQuantities(int $pohodaId, array $productIdsIndexedByPohodaIds, int $externalStockQuantity, int $externalStockId): void
    {
        if (!isset($productIdsIndexedByPohodaIds[$pohodaId])) {
            $this->logger->addError('Produkt při aktualizaci skladových zásob externího skladu nebyl nenalezen', [
                'pohodaId' => $pohodaId,
            ]);
            $this->notFoundProductPohodaIdsInEshop[] = $pohodaId;
            $this->updatedPohodaProductIds[] = $pohodaId;
            return;
        }

        $productId = $productIdsIndexedByPohodaIds[$pohodaId];
        $this->productStoreStockFacade->manualInsertStoreStock($productId, $externalStockId, $externalStockQuantity);

        $this->logger->addInfo('Produktu byla upravena skladová zásoba externího skladu', [
            'pohodaId' => $pohodaId,
            'productId' => $productId,
            'externalStockQuantity' => $externalStockQuantity,
        ]);

        $this->updatedPohodaProductIds[] = $pohodaId;
        $this->updatedProductIds[] = $productId;
    }

    /**
     * @param array $updatedProductIds
     */
    private function markProductsForRecalculation(array $updatedProductIds): void
    {
        $this->logger->addInfo('Označení produktů k přepočtu viditelností a exportu do Elasticsearch', [
            'updatedProductIds' => count($updatedProductIds),
        ]);
        $this->productFacade->manualMarkProductsForExportAndRecalculateAvailability($updatedProductIds);
    }
}
