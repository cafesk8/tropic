<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Product;

use App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataException;
use DateTime;
use Symfony\Bridge\Monolog\Logger;

class PohodaProductExportFacade
{
    /**
     * @var \App\Component\Transfer\Pohoda\Product\PohodaProductExportRepository
     */
    private $pohodaProductExportRepository;

    /**
     * @var \App\Component\Transfer\Pohoda\Product\PohodaProductDataValidator
     */
    private $pohodaProductDataValidator;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * @param \Symfony\Bridge\Monolog\Logger $logger
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProductExportRepository $pohodaProductExportRepository
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProductDataValidator $pohodaProductDataValidator
     */
    public function __construct(
        Logger $logger,
        PohodaProductExportRepository $pohodaProductExportRepository,
        PohodaProductDataValidator $pohodaProductDataValidator
    ) {
        $this->logger = $logger;
        $this->pohodaProductExportRepository = $pohodaProductExportRepository;
        $this->pohodaProductDataValidator = $pohodaProductDataValidator;
    }

    /**
     * @param \DateTime|null $lastModificationDate
     * @return \App\Component\Transfer\Pohoda\Product\PohodaProduct[]
     */
    public function findPohodaProductIdsFromLastModificationDate(?DateTime $lastModificationDate): array
    {
        return $this->pohodaProductExportRepository->findProductPohodaIdsByLastUpdateTime($lastModificationDate);
    }

    /**
     * @param array $pohodaProductIds
     * @return \App\Component\Transfer\Pohoda\Product\PohodaProduct[]
     */
    public function findPohodaProductsByPohodaIds(array $pohodaProductIds): array
    {
        $pohodaProductsResult = $this->pohodaProductExportRepository->findByPohodaProductIds(
            $pohodaProductIds
        );
        $this->addProductCategoriesToPohodaProductsResult($pohodaProductsResult, $pohodaProductIds);
        $this->addProductGroupsToPohodaProductsResult($pohodaProductsResult, $pohodaProductIds);
        $this->addRelatedProductsToPohodaProductsResult($pohodaProductsResult, $pohodaProductIds);
        $this->addProductVideosToPohodaProductsResult($pohodaProductsResult, $pohodaProductIds);

        $pohodaProductsResult = $this->reindexPohodaProductsResultByCatnums($pohodaProductsResult);
        $this->addStocksInformationToPohodaProductsResult($pohodaProductsResult);
        $this->addSaleInformationToPohodaProductsResult($pohodaProductsResult);

        return $this->getValidPohodaProducts($pohodaProductsResult);
    }

    /**
     * @param array $pohodaProductsData
     * @return \App\Component\Transfer\Pohoda\Product\PohodaProduct[]
     */
    private function getValidPohodaProducts(array $pohodaProductsData): array
    {
        $pohodaProducts = [];
        foreach ($pohodaProductsData as $pohodaProductData) {
            try {
                $this->pohodaProductDataValidator->validate($pohodaProductData);
            } catch (PohodaInvalidDataException $exc) {
                $this->logger->addError('Položka není validní a nebude přenesena.', [
                    'pohodaId' => $pohodaProductData[PohodaProduct::COL_POHODA_ID],
                    'productName' => $pohodaProductData[PohodaProduct::COL_NAME],
                    'exceptionMessage' => $exc->getMessage(),
                ]);
                continue;
            }

            $pohodaProducts[$pohodaProductData[PohodaProduct::COL_CATNUM]] = new PohodaProduct($pohodaProductData);
        }

        return $pohodaProducts;
    }

    /**
     * @param array $pohodaProductsResult
     */
    private function addStocksInformationToPohodaProductsResult(array &$pohodaProductsResult): void
    {
        $stocksInformation = $this->pohodaProductExportRepository->getStockInformationByCatnums(array_column($pohodaProductsResult, PohodaProduct::COL_CATNUM));
        foreach ($stocksInformation as $information) {
            if (isset($pohodaProductsResult[$information[PohodaProduct::COL_CATNUM]])) {
                $pohodaProductsResult[$information[PohodaProduct::COL_CATNUM]][PohodaProduct::COL_STOCKS_INFORMATION][$information[PohodaProduct::COL_STOCK_ID]] = (int)$information[PohodaProduct::COL_STOCK_TOTAL];

                // External stock is on product on default stock TROPIC
                if ((int)$information[PohodaProduct::COL_STOCK_ID] === PohodaProductExportRepository::DEFAULT_POHODA_STOCK_ID && $information[PohodaProduct::COL_EXTERNAL_STOCK] !== null) {
                    $pohodaProductsResult[$information[PohodaProduct::COL_CATNUM]][PohodaProduct::COL_STOCKS_INFORMATION][PohodaProductExportRepository::POHODA_STOCK_EXTERNAL_ID] = (int)$information[PohodaProduct::COL_EXTERNAL_STOCK];
                }
            }
        }
    }

    /**
     * @param array $pohodaProductsResult
     */
    private function addSaleInformationToPohodaProductsResult(array &$pohodaProductsResult): void
    {
        $saleInformation = $this->pohodaProductExportRepository->getSaleInformationByCatnums(array_column($pohodaProductsResult, PohodaProduct::COL_CATNUM));

        foreach ($saleInformation as $information) {
            if (isset($pohodaProductsResult[$information[PohodaProduct::COL_CATNUM]])) {
                $pohodaProductsResult[$information[PohodaProduct::COL_CATNUM]][PohodaProduct::COL_SALE_INFORMATION][$information[PohodaProduct::COL_STOCK_ID]] = $information[PohodaProduct::COL_SELLING_PRICE];
            }
        }
    }

    /**
     * @param array $pohodaProductsResult
     * @return array
     */
    private function reindexPohodaProductsResultByCatnums(array $pohodaProductsResult): array
    {
        $reindexedPohodaProductsResult = [];

        foreach ($pohodaProductsResult as $pohodaProductResult) {
            $reindexedPohodaProductsResult[$pohodaProductResult[PohodaProduct::COL_CATNUM]] = $pohodaProductResult;
            $reindexedPohodaProductsResult[$pohodaProductResult[PohodaProduct::COL_CATNUM]][PohodaProduct::COL_SALE_INFORMATION] = [];
            $reindexedPohodaProductsResult[$pohodaProductResult[PohodaProduct::COL_CATNUM]][PohodaProduct::COL_STOCKS_INFORMATION] = [
                PohodaProductExportRepository::POHODA_STOCK_SALE_ID => 0,
                PohodaProductExportRepository::POHODA_STOCK_STORE_ID => 0,
                PohodaProductExportRepository::POHODA_STOCK_TROPIC_ID => 0,
                PohodaProductExportRepository::POHODA_STOCK_STORE_SALE_ID => 0,
                PohodaProductExportRepository::POHODA_STOCK_EXTERNAL_ID => 0,
            ];
        }

        return $reindexedPohodaProductsResult;
    }

    /**
     * @return array
     */
    public function getLogs(): array
    {
        return array_filter($this->logger->getLogs(), function (array $log) {
            return $log['priority'] === Logger::ERROR;
        });
    }

    /**
     * @param array $pohodaProductsResult
     * @param array $pohodaProductIds
     */
    private function addProductCategoriesToPohodaProductsResult(array &$pohodaProductsResult, array $pohodaProductIds): void
    {
        $pohodaCategoryIds = $this->getProductCategoriesByPohodaIds($pohodaProductIds);

        foreach ($pohodaCategoryIds as $pohodaProductId => $pohodaCategoryId) {
            if (isset($pohodaProductsResult[$pohodaProductId])) {
                $pohodaProductsResult[$pohodaProductId][PohodaProduct::COL_PRODUCT_CATEGORIES] = $pohodaCategoryId;
            }
        }
    }

    /**
     * @param int[] $pohodaProductIds
     * @return array
     */
    public function getProductCategoriesByPohodaIds(array $pohodaProductIds): array
    {
        $pohodaProductCategories = $this->pohodaProductExportRepository->getProductCategoriesByPohodaIds($pohodaProductIds);
        $productCategories = [];

        foreach ($pohodaProductCategories as $pohodaProductCategory) {
            $pohodaCategoryId = (int)$pohodaProductCategory[PohodaProduct::COL_CATEGORY_REF_CATEGORY_ID];
            if ($pohodaCategoryId > 0) {
                $productCategories[(int)$pohodaProductCategory[PohodaProduct::COL_PRODUCT_REF_ID]][] = $pohodaCategoryId;
            }
        }

        return $productCategories;
    }

    /**
     * @param array $pohodaProductsResult
     * @param array $pohodaProductIds
     */
    private function addProductGroupsToPohodaProductsResult(array &$pohodaProductsResult, array $pohodaProductIds): void
    {
        $pohodaProductGroupItems = $this->pohodaProductExportRepository->getProductGroupsByPohodaIds($pohodaProductIds);
        foreach ($pohodaProductGroupItems as $pohodaGroupItem) {
            $mainProductPohodaId = (int)$pohodaGroupItem[PohodaProduct::COL_PRODUCT_REF_ID];
            if (isset($pohodaProductsResult[$mainProductPohodaId])) {
                $pohodaProductsResult[$mainProductPohodaId][PohodaProduct::COL_PRODUCT_GROUP_ITEMS][] = $pohodaGroupItem;
            }
        }
    }

    /**
     * @param array $pohodaProductsResult
     * @param array $pohodaProductIds
     */
    private function addRelatedProductsToPohodaProductsResult(array &$pohodaProductsResult, array $pohodaProductIds): void
    {
        $relatedProducts = $this->pohodaProductExportRepository->getRelatedProductsByPohodaIds($pohodaProductIds);
        foreach ($relatedProducts as $relatedProduct) {
            $mainProductPohodaId = (int)$relatedProduct[PohodaProduct::COL_PRODUCT_REF_ID];
            if (isset($pohodaProductsResult[$mainProductPohodaId])) {
                $pohodaProductsResult[$mainProductPohodaId][PohodaProduct::COL_RELATED_PRODUCTS][] = $relatedProduct;
            }
        }
    }

    /**
     * @param array $pohodaProductsResult
     * @param array $pohodaProductIds
     */
    private function addProductVideosToPohodaProductsResult(array &$pohodaProductsResult, array $pohodaProductIds): void
    {
        $productVideos = $this->pohodaProductExportRepository->getProductsVideosByPohodaIds($pohodaProductIds);
        foreach ($productVideos as $productVideo) {
            $productPohodaId = (int)$productVideo[PohodaProduct::COL_PRODUCT_REF_ID];
            if (isset($pohodaProductsResult[$productPohodaId])) {
                $pohodaProductsResult[$productPohodaId][PohodaProduct::COL_PRODUCT_VIDEOS][] = $productVideo[PohodaProduct::COL_POHODA_PRODUCT_VIDEO];
            }
        }
    }
}
