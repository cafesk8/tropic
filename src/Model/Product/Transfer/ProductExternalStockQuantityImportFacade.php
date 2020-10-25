<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Transfer\Logger\TransferLogger;
use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Component\Transfer\Pohoda\Product\PohodaProduct;
use App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade;
use App\Model\Product\Elasticsearch\ProductExportStockFacade;
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
    private array $pohodaProductIdsForRemoveFromQueue = [];

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

    private ProductExportStockFacade $productExportStockFacade;

    /**
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade $pohodaProductExportFacade
     * @param \App\Model\Product\Transfer\ProductExternalStockQuantityQueueImportFacade $productStockQuantityQueueImportFacade
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \App\Model\Store\StoreFacade $storeFacade
     * @param \App\Model\Product\StoreStock\ProductStoreStockFacade $productStoreStockFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\Elasticsearch\ProductExportStockFacade $productExportStockFacade
     */
    public function __construct(
        TransferLoggerFactory $transferLoggerFactory,
        PohodaProductExportFacade $pohodaProductExportFacade,
        ProductExternalStockQuantityQueueImportFacade $productStockQuantityQueueImportFacade,
        EntityManagerInterface $entityManager,
        StoreFacade $storeFacade,
        ProductStoreStockFacade $productStoreStockFacade,
        ProductFacade $productFacade,
        ProductExportStockFacade $productExportStockFacade
    ) {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(ProductExternalStockQuantityImportCronModule::TRANSFER_IDENTIFIER);
        $this->entityManager = $entityManager;
        $this->pohodaProductExportFacade = $pohodaProductExportFacade;
        $this->productExternalStockQuantityQueueImportFacade = $productStockQuantityQueueImportFacade;
        $this->storeFacade = $storeFacade;
        $this->productStoreStockFacade = $productStoreStockFacade;
        $this->productFacade = $productFacade;
        $this->productExportStockFacade = $productExportStockFacade;
    }

    /**
     * @return int[]
     */
    public function processImport(): array
    {
        $this->pohodaProductIdsForRemoveFromQueue = [];
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
            $this->pohodaProductIdsForRemoveFromQueue = array_filter($this->pohodaProductIdsForRemoveFromQueue);
            $this->updatedProductIds = array_filter($this->updatedProductIds);

            if (count($this->pohodaProductIdsForRemoveFromQueue) > 0) {
                $this->logger->addInfo('Proběhne smazání produktů z fronty skladových zásob', [
                    'updatedPohodaProductIdsCount' => count($this->pohodaProductIdsForRemoveFromQueue),
                ]);
                $this->productExternalStockQuantityQueueImportFacade->removeProductsFromQueue($this->pohodaProductIdsForRemoveFromQueue);
            }

            if (count($this->notFoundProductPohodaIdsInEshop) > 0) {
                $this->logger->addInfo('Nenalezené produkty v e-shopu', [
                    'count' => count($this->notFoundProductPohodaIdsInEshop),
                    'notFoundProductPohodaIdsInEshop' => $this->notFoundProductPohodaIdsInEshop,
                ]);
                $this->productExternalStockQuantityQueueImportFacade->removeProductsFromQueue($this->notFoundProductPohodaIdsInEshop);
            }
            $this->exportProducts($this->updatedProductIds);

            $this->logger->persistTransferIssues();
        }

        return $this->updatedProductIds;

    }

    /**
     * @param array $productsStockQuantities
     */
    private function updateProductsStockQuantities(array $productsStockQuantities): void
    {
        $productIdsWithAmountMultiplierIndexedByPohodaIds = $this->productFacade->getProductIdsIndexedByPohodaIds(array_keys($productsStockQuantities));
        $productIdsIndexedByPohodaIds = $this->getProductIdsIndexedByPohodaIds($productIdsWithAmountMultiplierIndexedByPohodaIds);
        $productAmountMultipliersIndexedByPohodaIds = $this->getAmountMultipliersIndexedByPohodaIds($productIdsWithAmountMultiplierIndexedByPohodaIds);
        $externalStock = $this->storeFacade->findExternalStock();
        if ($externalStock === null) {
            $this->logger->addError('Externí sklad v eshopu neexistuje. Import skladových zásob externího skladu nebude proveden!');
        }
        $currentStockQuantities = $this->productStoreStockFacade->getProductStockQuantities($productIdsIndexedByPohodaIds);

        foreach ($productsStockQuantities as $productStockQuantity) {
            $pohodaId = (int)$productStockQuantity[PohodaProduct::COL_POHODA_ID];
            $externalStockQuantity = (int)$productStockQuantity[PohodaProduct::COL_EXTERNAL_STOCK];

            try {
                $this->editProductExternalStockQuantity($pohodaId, $productIdsIndexedByPohodaIds, $productAmountMultipliersIndexedByPohodaIds, $externalStockQuantity, $externalStock->getId(), $currentStockQuantities);
            } catch (Exception $exc) {
                $this->logger->addError('Chyba importu skladových zásob externího skladu', [
                    'pohodaId' => $pohodaId,
                    'externalStockQuantity' => $externalStockQuantity,
                    'exceptionMessage' => $exc->getMessage(),
                ]);
            } finally {
                $this->pohodaProductIdsForRemoveFromQueue[] = $pohodaId;
            }

            $this->entityManager->clear();
        }
    }

    /**
     * @param int[][] $productIdsWithAmountMultiplierIndexedByPohodaIds
     * @return int[]
     */
    private function getAmountMultipliersIndexedByPohodaIds(array $productIdsWithAmountMultiplierIndexedByPohodaIds): array
    {
        $productAmountMultipliersIndexedByPohodaIds = [];
        foreach ($productIdsWithAmountMultiplierIndexedByPohodaIds as $pohodaId => $productIdWithAmountMultiplier) {
            $productAmountMultipliersIndexedByPohodaIds[$pohodaId] = $productIdWithAmountMultiplier['amountMultiplier'];
        }

        return $productAmountMultipliersIndexedByPohodaIds;
    }

    /**
     * @param int[][] $productIdsWithAmountMultiplierIndexedByPohodaIds
     * @return int[]
     */
    private function getProductIdsIndexedByPohodaIds(array $productIdsWithAmountMultiplierIndexedByPohodaIds): array
    {
        $productIdsIndexedByPohodaIds = [];
        foreach ($productIdsWithAmountMultiplierIndexedByPohodaIds as $pohodaId => $productIdWithAmountMultiplier) {
            $productIdsIndexedByPohodaIds[$pohodaId] = $productIdWithAmountMultiplier['productId'];
        }

        return $productIdsIndexedByPohodaIds;
    }

    /**
     * @param int $pohodaId
     * @param int[] $productIdsIndexedByPohodaIds
     * @param int[] $productAmountMultipliersIndexedByPohodaIds
     * @param int $newExternalStockQuantity
     * @param int $externalStockId
     * @param int[][] $currentStockQuantities
     */
    private function editProductExternalStockQuantity(
        int $pohodaId,
        array $productIdsIndexedByPohodaIds,
        array $productAmountMultipliersIndexedByPohodaIds,
        int $newExternalStockQuantity,
        int $externalStockId,
        array $currentStockQuantities
    ): void {
        if (!isset($productIdsIndexedByPohodaIds[$pohodaId])) {
            $this->logger->addError('Produkt při aktualizaci skladových zásob externího skladu nebyl nenalezen', [
                'pohodaId' => $pohodaId,
            ]);
            $this->notFoundProductPohodaIdsInEshop[] = $pohodaId;

            return;
        }

        $productId = $productIdsIndexedByPohodaIds[$pohodaId];
        $productAmountMultiplier = $productAmountMultipliersIndexedByPohodaIds[$pohodaId] ?? 1;
        $currentExternalStockQuantity = $currentStockQuantities[$productId][$externalStockId] ?? 0;
        if ($currentExternalStockQuantity === $newExternalStockQuantity) {
            $this->logger->addInfo('Produkt má stejnou skladovou zásobu. Skladová zásoba nebude aktualizována.', [
                'pohodaId' => $pohodaId,
                'productId' => $productId,
                'previousExternalStockQuantity' => $currentExternalStockQuantity,
                'newExternalStockQuantity' => $newExternalStockQuantity,
            ]);
            $this->pohodaProductIdsForRemoveFromQueue[] = $pohodaId;

            return;
        }
        $productCurrentStockQuantities = $currentStockQuantities[$productId] ?? [];
        $this->updateProductStockQuantity($productId, $pohodaId, $productCurrentStockQuantities, $productAmountMultiplier, $newExternalStockQuantity, $externalStockId);

        $this->updatedProductIds[] = $productId;
    }

    /**
     * @param int $productId
     * @param int $pohodaId
     * @param array $currentStockQuantities
     * @param int $productAmountMultiplier
     * @param int $newExternalStockQuantity
     * @param int $externalStockId
     */
    private function updateProductStockQuantity(
        int $productId,
        int $pohodaId,
        array $currentStockQuantities,
        int $productAmountMultiplier,
        int $newExternalStockQuantity,
        int $externalStockId
    ): void {
        $this->productStoreStockFacade->manualInsertStoreStock($productId, $externalStockId, $newExternalStockQuantity);

        $totalStockQuantity = 0;
        if (empty($currentStockQuantities)) {
            $totalStockQuantity += $newExternalStockQuantity;
        } else {
            foreach ($currentStockQuantities as $storeId => $stockQuantity) {
                if ($storeId === $externalStockId) {
                    $totalStockQuantity += $newExternalStockQuantity;
                } else {
                    $totalStockQuantity += $stockQuantity;
                }
            }
        }

        $realStockQuantity = $this->productFacade->calculateRealStockQuantity($totalStockQuantity, $productAmountMultiplier);
        $this->productFacade->manualUpdateProductStockQuantities($productId, $totalStockQuantity, $realStockQuantity);

        $this->logger->addInfo('Produktu byla upravena skladová zásoba externího skladu', [
            'pohodaId' => $pohodaId,
            'productId' => $productId,
            'previousStockQuantities' => $currentStockQuantities,
            'newExternalStockQuantity' => $newExternalStockQuantity,
            'totalStockQuantity' => $totalStockQuantity,
            'realStockQuantity' => $realStockQuantity,
        ]);
    }

    /**
     * @param array $updatedProductIds
     */
    private function exportProducts(array $updatedProductIds): void
    {
        if (count($updatedProductIds) > 0) {
            $this->logger->addInfo('Exportuji do Elasticsearch', [
                'currentTime' => new \DateTime(),
            ]);

            $exportedCountByDomainId = $this->productExportStockFacade->exportStockInformation($updatedProductIds);

            $this->logger->addInfo('Exportováno do Elasticsearch', [
                'exportedCountByDomainId' => $exportedCountByDomainId,
                'currentTime' => new \DateTime(),
            ]);
        }
    }
}
