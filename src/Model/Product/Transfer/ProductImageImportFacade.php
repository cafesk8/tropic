<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Domain\DomainHelper;
use App\Component\Image\ImageFacade;
use App\Component\Transfer\Logger\TransferLogger;
use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Component\Transfer\Mail\TransferMailFacade;
use App\Component\Transfer\Pohoda\Exception\PohodaMServerException;
use App\Component\Transfer\Pohoda\MServer\MServerClient;
use App\Component\Transfer\Pohoda\Product\Image\PohodaImage;
use App\Component\Transfer\Pohoda\Product\Image\PohodaImageExportFacade;
use App\Model\Product\Product;
use App\Model\Product\ProductFacade;
use DateTime;
use Exception;
use League\Flysystem\FilesystemInterface;
use Shopsys\FrameworkBundle\Component\String\TransformString;
use Shopsys\FrameworkBundle\Model\Mail\Exception\MailException;
use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportScheduler;

class ProductImageImportFacade
{
    private const PRODUCT_IMAGES_SUBDIR = 'product/original/';
    private const BATCH_LIMIT = 250;

    private FilesystemInterface $filesystem;

    private MServerClient $mServerClient;

    private TransferLogger $logger;

    private string $imagesDirectory;

    private ImageFacade $imageFacade;

    private ProductFacade $productFacade;

    private PohodaImageExportFacade $pohodaImageExportFacade;

    private ProductExportScheduler $productExportScheduler;

    private ImageImportQueueFacade $imageInfoQueueFacade;

    private TransferMailFacade $transferMailFacade;

    /**
     * @param string $imagesDirectory
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Component\Transfer\Pohoda\MServer\MServerClient $mServerClient
     * @param \League\Flysystem\FilesystemInterface $filesystem
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Component\Transfer\Pohoda\Product\Image\PohodaImageExportFacade $pohodaImageExportFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportScheduler $productExportScheduler
     * @param \App\Model\Product\Transfer\ImageImportQueueFacade $imageInfoQueueFacade
     * @param \App\Component\Transfer\Mail\TransferMailFacade $transferMailFacade
     */
    public function __construct(
        string $imagesDirectory,
        TransferLoggerFactory $transferLoggerFactory,
        MServerClient $mServerClient,
        FilesystemInterface $filesystem,
        ImageFacade $imageFacade,
        ProductFacade $productFacade,
        PohodaImageExportFacade $pohodaImageExportFacade,
        ProductExportScheduler $productExportScheduler,
        ImageImportQueueFacade $imageInfoQueueFacade,
        TransferMailFacade $transferMailFacade
    ) {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(ProductImageImportCronModule::TRANSFER_IDENTIFIER);
        $this->mServerClient = $mServerClient;
        $this->filesystem = $filesystem;
        $this->imagesDirectory = $imagesDirectory;
        $this->imageFacade = $imageFacade;
        $this->productFacade = $productFacade;
        $this->pohodaImageExportFacade = $pohodaImageExportFacade;
        $this->productExportScheduler = $productExportScheduler;
        $this->imageInfoQueueFacade = $imageInfoQueueFacade;
        $this->transferMailFacade = $transferMailFacade;
    }

    /**
     * @param \DateTime|null $lastStartAt
     */
    public function importImagesFromPohoda(?DateTime $lastStartAt): void
    {
        $imagesTargetPath = $this->getImagesTargetPath();
        $nextImageId = $this->imageFacade->getHighestImageId() + 1;
        $this->imageInfoQueueFacade->updateQueue($lastStartAt);
        $productPohodaIds = $this->imageInfoQueueFacade->getIdsForImport(self::BATCH_LIMIT);

        $pohodaImages = $this->pohodaImageExportFacade->getPohodaImages($productPohodaIds);
        $pohodaImagesCount = count($pohodaImages);
        if ($pohodaImagesCount > 0) {
            $this->logger->addInfo('Dojde k p??enosu obr??zk??', [
                'imagesCount' => $pohodaImagesCount,
            ]);
        } else {
            $this->logger->addInfo('????dn?? obr??zky k p??enesen??');
        }

        $pohodaImageIdsIndexedByProductId = [];
        $productsIndexedByPohodaId = [];
        $processedProductPohodaIds = [];
        $couldConnectToMserver = true;
        $productPohodaIdsWithoutImages = $productPohodaIds;

        foreach ($productPohodaIds as $productPohodaId) {
            $product = $this->productFacade->findByPohodaId($productPohodaId);
            if ($product !== null) {
                $pohodaImageIdsIndexedByProductId[$product->getId()] = [];
                $productsIndexedByPohodaId[$productPohodaId] = $product;
            }
        }

        foreach ($pohodaImages as $pohodaImage) {
            $productPohodaId = $pohodaImage->productPohodaId;
            $productIndex = array_search($productPohodaId, $productPohodaIdsWithoutImages, true);

            if ($productIndex !== false) {
                unset($productPohodaIdsWithoutImages[$productIndex]);
            }

            if (!isset($productsIndexedByPohodaId[$productPohodaId])) {
                $this->logger->addWarning('Product not found by Pohoda ID. Image will not be transferred', [
                    'productPohodaId' => $productPohodaId,
                    'imagePohodaId' => $pohodaImage->id,
                ]);
                continue;
            }

            $product = $productsIndexedByPohodaId[$productPohodaId];

            try {
                $this->processImage($pohodaImage, $imagesTargetPath, $nextImageId, $product);
            } catch (PohodaMServerException $ex) {
                if (str_contains($ex->getMessage(), '404')) {
                    $this->logger->addError('Obr??zek nenalezen', [
                        'message' => $ex->getMessage(),
                        'pohodaImage' => $pohodaImage,
                        'productId' => $product->getId(),
                        'catnum' => $product->getCatnum(),
                    ]);
                    $this->imageInfoQueueFacade->rescheduleImageImport($productPohodaId);
                } else {
                    $this->logger->addError('Probl??m s p??ipojen??m na mServer', [
                        'message' => $ex->getMessage(),
                        'pohodaImage' => $pohodaImage,
                        'productId' => $product->getId(),
                        'catnum' => $product->getCatnum(),
                    ]);
                    $couldConnectToMserver = false;
                    try {
                        $this->transferMailFacade->sendMailByErrorMessage($ex->getMessage());
                    } catch (\Swift_SwiftException | MailException $mailException) {
                        $this->logger->addError('Chyba p??i odes??l??n?? emailov?? notifikace o chyb?? mSeveru', [
                            'exceptionMessage' => $mailException->getMessage(),
                        ]);
                    }
                    break;
                }
            } catch (Exception $ex) {
                $this->logger->addError('P??i importu do??lo k chyb??', [
                    'message' => $ex->getMessage(),
                    'pohodaImage' => $pohodaImage,
                    'productId' => $product->getId(),
                    'catnum' => $product->getCatnum(),
                ]);
            }

            $nextImageId++;
            $this->imageFacade->restartImagesIdsDbSequence();
            $pohodaImageIdsIndexedByProductId[$product->getId()][] = $pohodaImage->id;
            $processedProductPohodaIds[] = $productPohodaId;
        }

        if ($couldConnectToMserver) {
            $this->deleteOrphanImages($pohodaImageIdsIndexedByProductId);
            foreach (array_keys($pohodaImageIdsIndexedByProductId) as $productId) {
                $this->productExportScheduler->scheduleRowIdForImmediateExport($productId);
            }
            $this->imageInfoQueueFacade->removeProductsFromQueue($processedProductPohodaIds);
            $this->imageInfoQueueFacade->removeProductsFromQueue($productPohodaIdsWithoutImages);
        }

        $this->logger->persistTransferIssues();
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Product\Image\PohodaImage $pohodaImage
     * @param string $imagesTargetPath
     * @param int $nextImageId
     * @param \App\Model\Product\Product $product
     */
    public function processImage(PohodaImage $pohodaImage, string $imagesTargetPath, int $nextImageId, Product $product): void
    {
        $imageByPohodaId = $this->imageFacade->findByPohodaId($pohodaImage->id);
        if ($imageByPohodaId !== null) {
            if ($imageByPohodaId->getPosition() !== $pohodaImage->position) {
                $this->imageFacade->updateImagePosition($imageByPohodaId->getId(), $pohodaImage->position);
                $this->logger->addInfo('Aktualizov??na pozice obr??zku', [
                    'pohodaImage' => $pohodaImage,
                    'productId' => $product->getId(),
                    'catnum' => $product->getCatnum(),
                ]);
            }
            if ($imageByPohodaId->getDescription() !== $pohodaImage->description) {
                $this->imageFacade->updateImageDescription($imageByPohodaId->getId(), $pohodaImage->description);
                $this->logger->addInfo('Aktualizov??n popis obr??zku', [
                    'pohodaImage' => $pohodaImage,
                    'productId' => $product->getId(),
                ]);
            }
            return;
        }
        $image = $this->mServerClient->getImage('/documents/Obr??zky/' . rawurlencode($pohodaImage->file));

        foreach (DomainHelper::LOCALES as $locale) {
            $imageTargetPath = $imagesTargetPath . TransformString::stringToFriendlyUrlSlug($product->getName($locale)) . '_' . $nextImageId . '.' . $pohodaImage->extension;
            $this->filesystem->put($imageTargetPath, $image);
        }

        $this->imageFacade->saveImageIntoDb(
            $product->getId(),
            'product',
            $nextImageId,
            $pohodaImage->extension,
            $pohodaImage->position,
            null,
            $pohodaImage->id,
            $pohodaImage->description
        );
        $this->logger->addInfo('Obr??zek ulo??en', [
            'pohodaImage' => $pohodaImage,
            'productId' => $product->getId(),
            'catnum' => $product->getCatnum(),
        ]);
    }

    /**
     * @param int[] $pohodaImageIdsIndexedByProductId
     */
    private function deleteOrphanImages(array $pohodaImageIdsIndexedByProductId): void
    {
        $deletedImageIds = $this->imageFacade->deleteImagesWithNotExistingPohodaId($pohodaImageIdsIndexedByProductId);
        if (!empty($deletedImageIds)) {
            $this->logger->addInfo('Odmaz??ny obr??zky, kter?? ji?? nejsou v Pohoda IS', [
                'deletedImageIds' => $deletedImageIds,
            ]);
        }
    }

    /**
     * @return string
     */
    private function getImagesTargetPath(): string
    {
        return $this->imagesDirectory . self::PRODUCT_IMAGES_SUBDIR;
    }
}
