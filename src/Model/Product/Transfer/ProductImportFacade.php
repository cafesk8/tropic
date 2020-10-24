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
use App\Model\Product\Transfer\Exception\DuplicateVariantIdException;
use App\Model\Product\Transfer\Exception\MainVariantNotFoundInEshopException;
use App\Model\Product\Transfer\Exception\ProductNotFoundInEshopException;
use App\Model\Product\Transfer\Exception\RelatedProductNotFoundException;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Shopsys\FrameworkBundle\Model\Product\Product;

class ProductImportFacade
{
    public const PRODUCT_EXPORT_MAX_BATCH_LIMIT = 250;

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
     * @var int[]
     */
    private array $updatedPohodaProductIds = [];

    /**
     * @var int[]
     */
    private array $notUpdatedPohodaProductIds = [];

    /**
     * @var \App\Component\EntityExtension\EntityManagerDecorator
     */
    private EntityManagerInterface $entityManager;

    /**
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Component\Transfer\Pohoda\Product\PohodaProductExportFacade $pohodaProductExportFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\ProductDataFactory $productDataFactory
     * @param \App\Model\Product\Transfer\PohodaProductMapper $pohodaProductMapper
     * @param \App\Model\Product\Transfer\ProductInfoQueueImportFacade $productInfoQueueImportFacade
     * @param \App\Component\EntityExtension\EntityManagerDecorator $entityManager
     */
    public function __construct(
        TransferLoggerFactory $transferLoggerFactory,
        PohodaProductExportFacade $pohodaProductExportFacade,
        ProductFacade $productFacade,
        ProductDataFactory $productDataFactory,
        PohodaProductMapper $pohodaProductMapper,
        ProductInfoQueueImportFacade $productInfoQueueImportFacade,
        EntityManagerInterface $entityManager
    ) {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(ProductImportCronModule::TRANSFER_IDENTIFIER);
        $this->pohodaProductExportFacade = $pohodaProductExportFacade;
        $this->productFacade = $productFacade;
        $this->productDataFactory = $productDataFactory;
        $this->pohodaProductMapper = $pohodaProductMapper;
        $this->productInfoQueueImportFacade = $productInfoQueueImportFacade;
        $this->entityManager = $entityManager;
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
        $returnedPohodaIds = array_map(fn (PohodaProduct $pohodaProduct) => $pohodaProduct->pohodaId, $pohodaProducts);
        $notExistingPohodaIds = array_diff(array_column($changedPohodaProductIds, 'pohodaId'), $returnedPohodaIds);
        $notExistingPohodaIdsCount = count($notExistingPohodaIds);
        if ($notExistingPohodaIdsCount > 0) {
            $this->logger->addInfo('Odmazávám z fronty produkty, které nejsou v Pohodě', [
                'count' => $notExistingPohodaIdsCount,
                'pohodaIds' => $notExistingPohodaIds,
            ]);
            $this->productInfoQueueImportFacade->removeProductsFromQueue($notExistingPohodaIds);
        }
        try {
            if (count($pohodaProducts) === 0) {
                $this->logger->addInfo('Nejsou žádná data ve frontě ke zpracování');
            } else {
                $this->logger->addInfo('Proběhne uložení produktů z fronty', [
                    'pohodaProductsCount' => count($pohodaProducts),
                ]);
                $this->updateProductsByPohodaProducts($pohodaProducts);
            }
        } catch (Exception $exception) {
            $this->logger->addError('Import produktů selhal', [
                'exceptionMessage' => $exception->getMessage(),
            ]);
        } finally {
            $this->updatedPohodaProductIds = array_filter($this->updatedPohodaProductIds);

            $this->logger->addInfo('Proběhne smazání produktů z fronty', [
                'updatedPohodaProductIdsCount' => count($this->updatedPohodaProductIds),
            ]);
            $this->productInfoQueueImportFacade->removeProductsFromQueue($this->updatedPohodaProductIds);

            $this->logger->addInfo('Proběhne nové přidání produktů do fronty - produkty, které je nutné zpracovat znova', [
                'productIdsToQueueAgainCount' => count($this->pohodaProductMapper->getProductIdsToQueueAgain()),
            ]);
            $this->productInfoQueueImportFacade->insertChangedPohodaProductIds(
                $this->pohodaProductMapper->getProductIdsToQueueAgain(),
                new DateTime()
            );

            $this->logger->addInfo('Proběhne zařazení produktů na konec fronty - nevalidní produkty', [
                'productIdsToQueueAgainCount' => count($this->notUpdatedPohodaProductIds),
            ]);
            $this->productInfoQueueImportFacade->moveProductsToEndOfQueue($this->notUpdatedPohodaProductIds);

            $this->logger->persistTransferIssues();
        }

        return $this->updatedPohodaProductIds;
    }

    /**
     * @param array $pohodaProducts
     */
    private function updateProductsByPohodaProducts(array $pohodaProducts): void
    {
        foreach ($pohodaProducts as $pohodaProduct) {
            $product = $this->productFacade->findByPohodaId($pohodaProduct->pohodaId);

            if ($product !== null) {
                $updatedPohodaProductId = $this->editProductByPohodaProduct($product, $pohodaProduct);
            } else {
                $updatedPohodaProductId = $this->createProductByPohodaProduct($pohodaProduct);
            }

            if ($updatedPohodaProductId !== null) {
                $this->updatedPohodaProductIds[] = $updatedPohodaProductId;
            } else {
                $this->notUpdatedPohodaProductIds[] = $pohodaProduct->pohodaId;
            }
            $this->entityManager->clear();
        }
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
            $this->logError('Import položky při vytvoření selhal', $exc, $pohodaProduct);

            return null;
        }

        $this->logger->addInfo('Produkt vytvořen', [
            'pohodaId' => $createdProduct->getPohodaId(),
            'productId' => $createdProduct->getId(),
            'catnum' => $createdProduct->getCatnum(),
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
            $this->logError('Import položky při úpravě selhal', $exc, $pohodaProduct);

            return null;
        }

        $this->logger->addInfo('Produkt upraven', [
            'pohodaId' => $editedProduct->getPohodaId(),
            'productId' => $editedProduct->getId(),
            'catnum' => $editedProduct->getCatnum(),
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
        } catch (RelatedProductNotFoundException $exception) {
            $this->logError('Pro tento produkt nebyl nalezen v e-shopu produkt s ním související', $exception, $pohodaProduct);

            return true;
        } catch (ProductNotFoundInEshopException $exception) {
            $this->logError('V e-shopu nebyl nalezen produkt, který patří do tohoto setu', $exception, $pohodaProduct);

            return false;
        } catch (MainVariantNotFoundInEshopException $exception) {
            $this->logError('Není možné vyvořit variantu pro kterou neexistuje odpovídající hlavní varianta', $exception, $pohodaProduct);

            return false;
        } catch (DuplicateVariantIdException $exception) {
            $this->logError('Zadané ID modifikace je již v systému přiřazeno jinému produktu', $exception, $pohodaProduct);

            return false;
        } catch (Exception $exception) {
            $this->logError('Namapování položky selhalo', $exception, $pohodaProduct);

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
