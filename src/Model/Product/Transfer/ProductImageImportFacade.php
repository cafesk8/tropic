<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Image\ImageFacade;
use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Component\Transfer\Pohoda\Exception\PohodaMServerException;
use App\Component\Transfer\Pohoda\MServer\MServerClient;
use App\Component\Transfer\Pohoda\Product\Image\PohodaImage;
use App\Component\Transfer\Pohoda\Product\Image\PohodaImageExportFacade;
use App\Model\Product\Product;
use App\Model\Product\ProductFacade;
use DateTime;
use League\Flysystem\FilesystemInterface;
use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportScheduler;

class ProductImageImportFacade
{
    private const PRODUCT_IMAGES_SUBDIR = 'product/original/';

    /**
     * @var \League\Flysystem\FilesystemInterface
     */
    private $filesystem;

    /**
     * @var \App\Component\Transfer\Pohoda\MServer\MServerClient
     */
    private $mServerClient;

    /**
     * @var \App\Component\Transfer\Logger\TransferLogger
     */
    private $logger;

    /**
     * @var string
     */
    private $imagesDirectory;

    /**
     * @var \App\Component\Image\ImageFacade
     */
    private $imageFacade;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Component\Transfer\Pohoda\Product\Image\PohodaImageExportFacade
     */
    private $pohodaImageExportFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportScheduler
     */
    private $productExportScheduler;

    /**
     * @param string $imagesDirectory
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Component\Transfer\Pohoda\MServer\MServerClient $mServerClient
     * @param \League\Flysystem\FilesystemInterface $filesystem
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Component\Transfer\Pohoda\Product\Image\PohodaImageExportFacade $pohodaImageExportFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportScheduler $productExportScheduler
     */
    public function __construct(
        string $imagesDirectory,
        TransferLoggerFactory $transferLoggerFactory,
        MServerClient $mServerClient,
        FilesystemInterface $filesystem,
        ImageFacade $imageFacade,
        ProductFacade $productFacade,
        PohodaImageExportFacade $pohodaImageExportFacade,
        ProductExportScheduler $productExportScheduler
    ) {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(ProductImageImportCronModule::TRANSFER_IDENTIFIER);
        $this->mServerClient = $mServerClient;
        $this->filesystem = $filesystem;
        $this->imagesDirectory = $imagesDirectory;
        $this->imageFacade = $imageFacade;
        $this->productFacade = $productFacade;
        $this->pohodaImageExportFacade = $pohodaImageExportFacade;
        $this->productExportScheduler = $productExportScheduler;
    }

    /**
     * @param \DateTime|null $lastFinishAt
     */
    public function importImagesFromPohoda(?DateTime $lastFinishAt): void
    {
        $imagesTargetPath = $this->getImagesTargetPath();
        $nextImageId = $this->imageFacade->getHighestImageId() + 1;
        $productPohodaIds = $this->productFacade->getPohodaIdsForProductsUpdatedSince($lastFinishAt);

        $pohodaImages = $this->pohodaImageExportFacade->getPohodaImages($productPohodaIds);
        $pohodaImagesCount = count($pohodaImages);
        if ($pohodaImagesCount > 0) {
            $this->logger->addInfo('Dojde k přenosu obrázků', [
                'imagesCount' => $pohodaImagesCount,
            ]);
        } else {
            $this->logger->addInfo('Žádné obrázky k přenesení');
        }

        $pohodaImageIdsIndexedByProductId = [];
        $productsIndexedByPohodaId = [];
        foreach ($productPohodaIds as $productPohodaId) {
            $product = $this->productFacade->findByPohodaId($productPohodaId);
            if ($product !== null) {
                $pohodaImageIdsIndexedByProductId[$product->getId()] = [];
                $productsIndexedByPohodaId[$productPohodaId] = $product;
            }
        }
        foreach ($pohodaImages as $pohodaImage) {
            $productPohodaId = $pohodaImage->productPohodaId;
            if (!isset($productsIndexedByPohodaId[$productPohodaId])) {
                $this->logger->addWarning('Product not found by Pohoda ID. Image will not be transferred', [
                    'productPohodaId' => $productPohodaId,
                    'imagePohodaId' => $pohodaImage->id,
                ]);
                continue;
            }

            $product = $productsIndexedByPohodaId[$productPohodaId];
            $this->processImage($pohodaImage, $imagesTargetPath, $nextImageId, $product);
            $nextImageId++;
            $this->imageFacade->restartImagesIdsDbSequence($nextImageId);
            $pohodaImageIdsIndexedByProductId[$product->getId()][] = $pohodaImage->id;
        }

        $this->deleteOrphanImages($pohodaImageIdsIndexedByProductId);
        foreach (array_keys($pohodaImageIdsIndexedByProductId) as $productId) {
            $this->productExportScheduler->scheduleRowIdForImmediateExport($productId);
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
        try {
            $imageByPohodaId = $this->imageFacade->findByPohodaId($pohodaImage->id);
            if ($imageByPohodaId !== null) {
                if ($imageByPohodaId->getPosition() !== $pohodaImage->position) {
                    $this->imageFacade->updateImagePosition($imageByPohodaId->getId(), $pohodaImage->position);
                    $this->logger->addInfo('Aktualizována pozice obrázku', [
                        'pohodaImage' => $pohodaImage,
                        'productId' => $product->getId(),
                    ]);
                }
                return;
            }
            $image = $this->mServerClient->getImage('/documents/Obrázky/' . rawurlencode($pohodaImage->file));
            $imageTargetPath = $imagesTargetPath . $nextImageId . '.' . $pohodaImage->extension;
            $this->filesystem->put($imageTargetPath, $image);
            $this->imageFacade->saveImageIntoDb(
                $product->getId(),
                'product',
                $nextImageId,
                $pohodaImage->extension,
                $pohodaImage->position,
                null,
                $pohodaImage->id
            );
            $this->logger->addInfo('Obrázek uložen', [
                'pohodaImage' => $pohodaImage,
                'productId' => $product->getId(),
            ]);
        } catch (PohodaMServerException $ex) {
            $this->logger->addError('Problém s připojením na mServer', [
                'message' => $ex->getMessage(),
                'pohodaImage' => $pohodaImage,
            ]);
        } catch (\Exception $ex) {
            $this->logger->addError('Při importu došlo k chybě', [
                'message' => $ex->getMessage(),
                'pohodaImage' => $pohodaImage,
            ]);
        }
    }

    /**
     * @param int[] $pohodaImageIdsIndexedByProductId
     */
    private function deleteOrphanImages(array $pohodaImageIdsIndexedByProductId): void
    {
        $deletedImageIds = $this->imageFacade->deleteImagesWithNotExistingPohodaId($pohodaImageIdsIndexedByProductId);
        if (!empty($deletedImageIds)) {
            $this->logger->addInfo('Odmazány obrázky, které již nejsou v Pohoda IS', [
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