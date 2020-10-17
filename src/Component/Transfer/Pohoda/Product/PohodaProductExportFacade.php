<?php

declare(strict_types=1);

namespace App\Component\Transfer\Pohoda\Product;

use App\Component\Domain\DomainHelper;
use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataException;
use App\Model\Product\Transfer\ProductImportCronModule;
use App\Model\Store\StoreFacade;
use DateTime;

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
     * @var \App\Component\Transfer\Logger\TransferLogger
     */
    private $logger;

    private StoreFacade $storeFacade;

    /**
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProductExportRepository $pohodaProductExportRepository
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProductDataValidator $pohodaProductDataValidator
     * @param \App\Model\Store\StoreFacade $storeFacade
     */
    public function __construct(
        TransferLoggerFactory $transferLoggerFactory,
        PohodaProductExportRepository $pohodaProductExportRepository,
        PohodaProductDataValidator $pohodaProductDataValidator,
        StoreFacade $storeFacade
    ) {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(ProductImportCronModule::TRANSFER_IDENTIFIER);
        $this->pohodaProductExportRepository = $pohodaProductExportRepository;
        $this->pohodaProductDataValidator = $pohodaProductDataValidator;
        $this->storeFacade = $storeFacade;
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
        $this->addProductSetsToPohodaProductsResult($pohodaProductsResult, $pohodaProductIds);
        $this->addRelatedProductsToPohodaProductsResult($pohodaProductsResult, $pohodaProductIds);
        $this->addProductVideosToPohodaProductsResult($pohodaProductsResult, $pohodaProductIds);
        $this->addProductParametersToPohodaProductsResult($pohodaProductsResult, $pohodaProductIds);

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
                $this->logger->addError('Produkt není validní a nebude přenesen.', [
                    'pohodaId' => $pohodaProductData[PohodaProduct::COL_POHODA_ID],
                    'productName' => $pohodaProductData[PohodaProduct::COL_NAME],
                    'exceptionMessage' => $exc->getMessage(),
                ]);
                continue;
            }

            $pohodaProducts[$pohodaProductData[PohodaProduct::COL_CATNUM]] = new PohodaProduct($pohodaProductData);
        }
        $this->logger->persistTransferIssues();

        return $pohodaProducts;
    }

    /**
     * @param array $pohodaProductsResult
     */
    private function addStocksInformationToPohodaProductsResult(array &$pohodaProductsResult): void
    {
        $stocksInformation = $this->pohodaProductExportRepository->getStockInformationByCatnums(array_column($pohodaProductsResult, PohodaProduct::COL_CATNUM));
        $defaultPohodaStockExternalNumber = $this->storeFacade->getDefaultPohodaStockExternalNumber();
        $externalPohodaStockExternalNumber = $this->storeFacade->getPohodaStockExternalExternalNumber();
        $pohodaStockStoreExternalNumber = $this->storeFacade->getPohodaStockStoreExternalNumber();
        foreach ($stocksInformation as $information) {
            if (isset($pohodaProductsResult[$information[PohodaProduct::COL_CATNUM]])) {
                $supplierName = $pohodaProductsResult[$information[PohodaProduct::COL_CATNUM]][PohodaProduct::COL_PRODUCT_SUPPLIER_NAME];

                // If product (internal stock TROPIC) have supplier "2prod", stock quantity at store is always zero
                if ((int)$information[PohodaProduct::COL_STOCK_ID] === $pohodaStockStoreExternalNumber && $supplierName === PohodaProduct::INTERNAL_SUPPLIER_NAME) {
                    $stockQuantity = 0;
                } else {
                    $stockQuantity = (int)$information[PohodaProduct::COL_STOCK_TOTAL];
                }
                $pohodaProductsResult[$information[PohodaProduct::COL_CATNUM]][PohodaProduct::COL_STOCKS_INFORMATION][$information[PohodaProduct::COL_STOCK_ID]] = $stockQuantity;

                // External stock is on product on default stock TROPIC
                if ((int)$information[PohodaProduct::COL_STOCK_ID] === $defaultPohodaStockExternalNumber && $information[PohodaProduct::COL_EXTERNAL_STOCK] !== null) {
                    $pohodaProductsResult[$information[PohodaProduct::COL_CATNUM]][PohodaProduct::COL_STOCKS_INFORMATION][$externalPohodaStockExternalNumber] = (int)$information[PohodaProduct::COL_EXTERNAL_STOCK];
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
                $this->storeFacade->getPohodaStockSaleExternalNumber() => 0,
                $this->storeFacade->getPohodaStockStoreExternalNumber() => 0,
                $this->storeFacade->getPohodaStockTropicExternalNumber() => 0,
                $this->storeFacade->getPohodaStockStoreSaleExternalNumber() => 0,
                $this->storeFacade->getPohodaStockExternalExternalNumber() => 0,
            ];
        }

        return $reindexedPohodaProductsResult;
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
    private function addProductSetsToPohodaProductsResult(array &$pohodaProductsResult, array $pohodaProductIds): void
    {
        $pohodaProductSetItems = $this->pohodaProductExportRepository->getProductSetsByPohodaIds($pohodaProductIds);
        foreach ($pohodaProductSetItems as $pohodaSetItem) {
            $mainProductPohodaId = (int)$pohodaSetItem[PohodaProduct::COL_PRODUCT_REF_ID];
            if (isset($pohodaProductsResult[$mainProductPohodaId])) {
                $pohodaProductsResult[$mainProductPohodaId][PohodaProduct::COL_PRODUCT_SET_ITEMS][] = $pohodaSetItem;
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

    /**
     * @param array $pohodaProductsResult
     * @param array $pohodaProductIds
     */
    private function addProductParametersToPohodaProductsResult(array &$pohodaProductsResult, array $pohodaProductIds): void
    {
        $productParameters = $this->pohodaProductExportRepository->getProductParametersByPohodaIds($pohodaProductIds);
        foreach ($productParameters as $productParameterPohodaId => $productParameter) {
            $productPohodaId = (int)$productParameter[PohodaProduct::COL_PRODUCT_REF_ID];
            $parameterValue = $this->getPohodaParameterValueByType($productParameter);
            if (isset($pohodaProductsResult[$productPohodaId]) && $parameterValue !== null) {
                $pohodaParameterValuesArray = explode('*', $parameterValue);
                $pohodaParameterValues = [
                    DomainHelper::CZECH_LOCALE => $pohodaParameterValuesArray[0],
                    DomainHelper::SLOVAK_LOCALE => $pohodaParameterValuesArray[1] ?? $pohodaParameterValuesArray[0],
                ];
                $pohodaParameter = new PohodaParameter(
                    trim($productParameter[PohodaProduct::COL_PARAMETER_NAME]),
                    $pohodaParameterValues,
                    (int)$productParameter[PohodaProduct::COL_PARAMETER_TYPE],
                    (int)$productParameter[PohodaProduct::COL_PARAMETER_VALUE_POSITION]
                );

                if (!$this->isParamDuplicate($pohodaProductsResult[$productPohodaId][PohodaProduct::COL_PARAMETERS], $pohodaParameter)) {
                    $pohodaProductsResult[$productPohodaId][PohodaProduct::COL_PARAMETERS][] = $pohodaParameter;
                }
            }
        }
    }

    /**
     * @param array $pohodaParameter
     * @return string|null
     */
    private function getPohodaParameterValueByType(array $pohodaParameter): ?string
    {
        $pohodaParameterType = (int)$pohodaParameter[PohodaProduct::COL_PARAMETER_TYPE];
        if (in_array($pohodaParameterType, PohodaParameter::POHODA_PARAMETER_COL_TYPE_NUMBER, true)) {
            return $pohodaParameter[PohodaProduct::COL_PARAMETER_VALUE_TYPE_NUMBER];
        }

        if ($pohodaParameterType === PohodaParameter::POHODA_PARAMETER_TYPE_LIST_ID) {
            return $pohodaParameter[PohodaProduct::COL_PARAMETER_VALUE_TYPE_LIST];
        }

        return $pohodaParameter[PohodaProduct::COL_PARAMETER_VALUE_TYPE_TEXT];
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Product\PohodaParameter[] $params
     * @param \App\Component\Transfer\Pohoda\Product\PohodaParameter $parameter
     * @return bool
     */
    private function isParamDuplicate(array $params, PohodaParameter $parameter): bool
    {
        foreach ($params as $param) {
            if ($param->name === $parameter->name && $param->type === $parameter->type) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $mainVariantId
     * @return int[]
     */
    public function getVariantIdsByMainVariantId(string $mainVariantId): array
    {
        return array_map(fn (array $variant) => $variant[PohodaProduct::COL_POHODA_ID], $this->pohodaProductExportRepository->getVariantIdsByMainVariantId($mainVariantId));
    }

    /**
     * @param \DateTime|null $lastUpdateTime
     * @return array
     */
    public function getPohodaProductIdsByExternalStockLastUpdateTime(?DateTime $lastUpdateTime): array
    {
        return $this->pohodaProductExportRepository->getPohodaProductIdsByExternalStockLastUpdateTime($lastUpdateTime);
    }

    /**
     * @param array $pohodaProductIds
     * @return array
     */
    public function getPohodaProductExternalStockQuantitiesByProductIds(array $pohodaProductIds): array
    {
        return $this->pohodaProductExportRepository->getPohodaProductExternalStockQuantitiesByProductIds($pohodaProductIds);
    }
}
