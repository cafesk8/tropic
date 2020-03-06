<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Component\Transfer\Pohoda\Product\PohodaProduct;
use App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade;
use App\Model\Product\ProductDataFactory;
use App\Model\Product\ProductFacade;
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
        $this->pohodaProductExportFacade = $pohodaProductExportFacade;
        $this->productFacade = $productFacade;
        $this->productDataFactory = $productDataFactory;
        $this->pohodaProductMapper = $pohodaProductMapper;

        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(ProductImportCronModule::TRANSFER_IDENTIFIER);
        $this->productInfoQueueImportFacade = $productInfoQueueImportFacade;
    }

    /**
     * @return bool
     */
    public function processImport(): bool
    {
        $changedPohodaProductIds = $this->productInfoQueueImportFacade->findChangedPohodaProductIds(self::PRODUCT_EXPORT_MAX_BATCH_LIMIT);
        $pohodaProducts = $this->pohodaProductExportFacade->findPohodaProductsByPohodaIds(
            $changedPohodaProductIds
        );
        $updatedPohodaProductIds = [];

        if (count($pohodaProducts) === 0) {
            $this->logger->addInfo('Nejsou žádná data ke zpracování');
        } else {
            $updatedPohodaProductIds = $this->updateProductsByPohodaProducts($pohodaProducts);
        }

        $this->productInfoQueueImportFacade->removeProductsFromQueue($updatedPohodaProductIds);

        return false;
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

        return $updatedPohodaProductIds;
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProduct $pohodaProduct
     * @return int
     */
    private function createProductByPohodaProduct(PohodaProduct $pohodaProduct): int
    {
        $productData = $this->productDataFactory->create();

        try {
            $this->pohodaProductMapper->mapPohodaProductToProductData($pohodaProduct, $productData);
        } catch (Exception $exc) {
            $this->logger->addError('Vytvoření položky selhalo', [
                'pohodaId' => $pohodaProduct->pohodaId,
                'productName' => $pohodaProduct->name,
                'exceptionMessage' => $exc->getMessage(),
            ]);
        }

        $createdProduct = $this->productFacade->create($productData);

        return $createdProduct->getPohodaId();
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProduct $pohodaProduct
     * @return int
     */
    private function editProductByPohodaProduct(Product $product, PohodaProduct $pohodaProduct): int
    {
        $productData = $this->productDataFactory->createFromProduct($product);
        try {
            $this->pohodaProductMapper->mapPohodaProductToProductData($pohodaProduct, $productData);
        } catch (Exception $exc) {
            $this->logger->addError('Editace položky selhala.', [
                'productId' => $product->getId(),
                'productName' => $pohodaProduct->name,
                'exceptionMessage' => $exc->getMessage(),
            ]);
        }
        $editedProduct = $this->productFacade->edit($product->getId(), $productData);

        return $editedProduct->getPohodaId();
    }
}
