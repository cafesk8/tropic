<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Component\Transfer\Pohoda\Product\PohodaProduct;
use App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade;
use App\Model\Product\ProductData;
use App\Model\Product\ProductDataFactory;
use App\Model\Product\ProductFacade;
use App\Model\Product\Transfer\Exception\CategoryDoesntExistInEShopException;
use App\Model\Product\Transfer\Exception\ProductNotFoundInEshopException;
use Exception;
use Shopsys\FrameworkBundle\Model\Product\Product;

class ProductImportFacade
{
    public const PRODUCT_EXPORT_MAX_BATCH_LIMIT = 1000;

    /**
     * @var \App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade
     */
    private $pohodaProductExportFacade;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Model\Product\ProductDataFactory
     */
    private $productDataFactory;

    /**
     * @var \App\Model\Product\Transfer\PohodaProductMapper
     */
    private $pohodaProductMapper;

    /**
     * @var \App\Component\Transfer\Logger\TransferLogger
     */
    private $logger;

    /**
     * @var \App\Model\Product\Transfer\ProductInfoQueueImportFacade
     */
    private $productInfoQueueImportFacade;

    /**
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade $pohodaProductExportFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\ProductDataFactory $productDataFactory
     * @param \App\Model\Product\Transfer\PohodaProductMapper $pohodaProductMapper
     * @param \App\Model\Product\Transfer\ProductInfoQueueImportFacade $productInfoQueueImportFacade
     */
    public function __construct(
        TransferLoggerFactory $transferLoggerFactory,
        PohodaProductExportFacade $pohodaProductExportFacade,
        ProductFacade $productFacade,
        ProductDataFactory $productDataFactory,
        PohodaProductMapper $pohodaProductMapper,
        ProductInfoQueueImportFacade $productInfoQueueImportFacade
    ) {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(ProductImportCronModule::TRANSFER_IDENTIFIER);
        $this->pohodaProductExportFacade = $pohodaProductExportFacade;
        $this->productFacade = $productFacade;
        $this->productDataFactory = $productDataFactory;
        $this->pohodaProductMapper = $pohodaProductMapper;
        $this->productInfoQueueImportFacade = $productInfoQueueImportFacade;
    }

    /**
     * @return int[]
     */
    public function processImport(): array
    {
        $changedPohodaProductIds = $this->productInfoQueueImportFacade->findChangedPohodaProductIds(self::PRODUCT_EXPORT_MAX_BATCH_LIMIT);
        $pohodaProducts = $this->pohodaProductExportFacade->findPohodaProductsByPohodaIds(
            $changedPohodaProductIds
        );
        $updatedPohodaProductIds = [];
        if (count($pohodaProducts) === 0) {
            $this->logger->addInfo('Nejsou žádná data ve frontě ke zpracování');
        } else {
            $this->logger->addInfo('Proběhne uložení produktů z fronty', ['pohodaProductsCount' => count($pohodaProducts)]);
            $updatedPohodaProductIds = $this->updateProductsByPohodaProducts($pohodaProducts);
        }
        $this->productInfoQueueImportFacade->removeProductsFromQueue($updatedPohodaProductIds);
        $this->logger->persistTransferIssues();

        return $changedPohodaProductIds;
    }

    /**
     * @param array $pohodaProducts
     * @return int[]
     */
    private function updateProductsByPohodaProducts(array $pohodaProducts): array
    {
        $updatedPohodaProductIds = [];
        foreach ($pohodaProducts as $pohodaProduct) {
            $product = $this->productFacade->findByPohodaId($pohodaProduct->pohodaId);

            if ($product !== null) {
                $updatedPohodaProductIds[] = $this->editProductByPohodaProduct($product, $pohodaProduct);
            } else {
                $updatedPohodaProductIds[] = $this->createProductByPohodaProduct($pohodaProduct);
            }
        }

        return array_filter($updatedPohodaProductIds);
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProduct $pohodaProduct
     * @return int|null
     */
    private function createProductByPohodaProduct(PohodaProduct $pohodaProduct): ?int
    {
        $productData = $this->productDataFactory->create();

        if (!$this->mapProduct($pohodaProduct, $productData)) {
            return null;
        }

        try {
            $createdProduct = $this->productFacade->create($productData);
        } catch (Exception $exc) {
            $this->logError('Import položky selhal.', $exc, $pohodaProduct);

            return null;
        }

        $this->logger->addInfo('Produkt vytvořen', [
            'pohodaId' => $createdProduct->getPohodaId(),
            'productId' => $createdProduct->getId(),
        ]);

        return $createdProduct->getPohodaId();
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProduct $pohodaProduct
     * @return int|null
     */
    private function editProductByPohodaProduct(Product $product, PohodaProduct $pohodaProduct): ?int
    {
        $productData = $this->productDataFactory->createFromProduct($product);

        if (!$this->mapProduct($pohodaProduct, $productData)) {
            return null;
        }

        try {
            $editedProduct = $this->productFacade->edit($product->getId(), $productData);
        } catch (Exception $exc) {
            $this->logError('Import položky selhal.', $exc, $pohodaProduct);

            return null;
        }

        $this->logger->addInfo('Produkt upraven', [
            'pohodaId' => $editedProduct->getPohodaId(),
            'productId' => $editedProduct->getId(),
        ]);

        return $editedProduct->getPohodaId();
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProduct $pohodaProduct
     * @param \App\Model\Product\ProductData $productData
     * @return bool
     */
    private function mapProduct(PohodaProduct $pohodaProduct, ProductData $productData): bool
    {
        try {
            $this->pohodaProductMapper->mapPohodaProductToProductData($pohodaProduct, $productData);
        } catch (CategoryDoesntExistInEShopException $exception) {
            $this->logError('Kategorie nebyla v e-shopu nalezena', $exception, $pohodaProduct);

            return false;
        } catch (ProductNotFoundInEshopException $exception) {
            $this->logError('Pro tento produkt nebyl nalezen v e-shopu produkt s ním související', $exception, $pohodaProduct);

            return false;
        } catch (Exception $exception) {
            $this->logError('Import položky selhal.', $exception, $pohodaProduct);

            return false;
        }

        return true;
    }

    /**
     * @param string $logMessage
     * @param \Exception $exception
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProduct $pohodaProduct
     */
    private function logError(string $logMessage, Exception $exception, PohodaProduct $pohodaProduct): void
    {
        $this->logger->addError($logMessage, [
            'pohodaId' => $pohodaProduct->pohodaId,
            'productName' => $pohodaProduct->name,
            'catnum' => $pohodaProduct->catnum,
            'exceptionMessage' => $exception->getMessage(),
        ]);
    }
}
