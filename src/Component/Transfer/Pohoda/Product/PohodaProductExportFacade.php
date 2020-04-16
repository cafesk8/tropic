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
        $pohodaProductsResult = $this->reindexPohodaProductsResultByCatnums($pohodaProductsResult);
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
}
