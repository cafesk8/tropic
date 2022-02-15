<?php

declare(strict_types=1);

namespace App\Component\Image;

use App\Component\Domain\DomainHelper;
use App\Component\Transfer\Pohoda\Product\Image\PohodaImage;
use App\Model\Product\Product;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FileExistsException;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use Shopsys\Cdn\Component\Image\ImageFacade as BaseImageFacade;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\FileUpload\FileUpload;
use Shopsys\FrameworkBundle\Component\FileUpload\ImageUploadData;
use Shopsys\FrameworkBundle\Component\Image\AdditionalImageData;
use Shopsys\FrameworkBundle\Component\Image\Config\ImageConfig;
use Shopsys\FrameworkBundle\Component\Image\Exception\ImageNotFoundException;
use Shopsys\FrameworkBundle\Component\Image\Image;
use Shopsys\FrameworkBundle\Component\Image\ImageFactoryInterface;
use Shopsys\FrameworkBundle\Component\Image\ImageLocator;
use Shopsys\FrameworkBundle\Component\Image\ImageRepository;
use Shopsys\FrameworkBundle\Component\String\TransformString;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 * @property \App\Component\FileUpload\FileUpload $fileUpload
 * @property \App\Component\Image\ImageRepository $imageRepository
 * @method \App\Component\Image\Image[] getAllImagesByEntity(object $entity)
 * @method \Shopsys\FrameworkBundle\Component\Image\AdditionalImageData[] getAdditionalImagesData(\Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig, \App\Component\Image\Image $imageOrEntity, string|null $sizeName, string|null $type)
 * @method string getAdditionalImageUrl(\Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig, int $additionalSizeIndex, \App\Component\Image\Image $image, string|null $sizeName)
 * @method \App\Component\Image\Image getImageByObject(\App\Component\Image\Image|object $imageOrEntity, string|null $type)
 * @method \App\Component\Image\Image getById(int $imageId)
 * @method setImagePositionsByOrder(\App\Component\Image\Image[] $orderedImages)
 * @property \App\Component\Image\ImageLocator $imageLocator
 * @property \App\Component\Image\ImageFactory $imageFactory
 */
class ImageFacade extends BaseImageFacade
{
    private ImageCacheFacade $imageCacheFacade;

    private string $productImageDir;

    /**
     * @param string $imageUrlPrefix
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Component\Image\Config\ImageConfig $imageConfig
     * @param \App\Component\Image\ImageRepository $imageRepository
     * @param \League\Flysystem\FilesystemInterface $filesystem
     * @param \App\Component\FileUpload\FileUpload $fileUpload
     * @param \App\Component\Image\ImageLocator $imageLocator
     * @param \Shopsys\FrameworkBundle\Component\Image\ImageFactoryInterface $imageFactory
     * @param \League\Flysystem\MountManager $mountManager
     * @param string $productImageDir
     * @param \App\Component\Image\ImageCacheFacade $imageCacheFacade
     */
    public function __construct(
        $imageUrlPrefix,
        EntityManagerInterface $em,
        ImageConfig $imageConfig,
        ImageRepository $imageRepository,
        FilesystemInterface $filesystem,
        FileUpload $fileUpload,
        ImageLocator $imageLocator,
        ImageFactoryInterface $imageFactory,
        MountManager $mountManager,
        string $productImageDir,
        ImageCacheFacade $imageCacheFacade
    ) {
        parent::__construct($imageUrlPrefix, $em, $imageConfig, $imageRepository, $filesystem, $fileUpload, $imageLocator, $imageFactory, $mountManager);

        $this->productImageDir = $productImageDir;
        $this->imageCacheFacade = $imageCacheFacade;
    }

    /**
     * @param object $entity
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return string[]
     */
    public function getAllImagesUrlsByEntity(object $entity, DomainConfig $domainConfig): array
    {
        $allImagesUrls = [];
        $allImages = $this->getImagesByEntityIndexedById($entity, null);

        foreach ($allImages as $image) {
            $allImagesUrls[] = $this->getImageUrl($domainConfig, $image, null, null);
        }

        return $allImagesUrls;
    }

    /**
     * @return int
     */
    public function getHighestImageId(): int
    {
        return $this->imageRepository->getHighestImageId();
    }

    /**
     * @param int $entityId
     * @param string $entityName
     * @param int $imageId
     * @param string $extension
     * @param int|null $position
     * @param string|null $type
     * @param int|null $pohodaId
     * @param string|null $description
     */
    public function saveImageIntoDb(
        int $entityId,
        string $entityName,
        int $imageId,
        string $extension,
        ?int $position = null,
        ?string $type = null,
        ?int $pohodaId = null,
        ?string $description = null
    ): void {
        $this->imageRepository->saveImageIntoDb($entityId, $entityName, $imageId, $extension, $position, $type, $pohodaId, $description);
    }

    public function restartImagesIdsDbSequence(): void
    {
        $this->imageRepository->restartImagesIdsDbSequence();
    }

    /**
     * @param int $pohodaId
     * @return \App\Component\Image\Image|null
     */
    public function findByPohodaId(int $pohodaId): ?Image
    {
        return $this->imageRepository->findByPohodaId($pohodaId);
    }

    /**
     * @param int $imageId
     * @param int $position
     */
    public function updateImagePosition(int $imageId, int $position): void
    {
        $this->imageRepository->updateImagePosition($imageId, $position);
    }

    /**
     * @param int $imageId
     * @param string|null $description
     */
    public function updateImageDescription(int $imageId, ?string $description): void
    {
        $this->imageRepository->updateImageDescription($imageId, $description);
    }

    /**
     * @param int[] $currentPohodaImageIdsIndexedByProductId
     * @return int[]
     */
    public function deleteImagesWithNotExistingPohodaId(array $currentPohodaImageIdsIndexedByProductId): array
    {
        return $this->imageRepository->deleteImagesWithNotExistingPohodaId($currentPohodaImageIdsIndexedByProductId);
    }

    /**
     * @param string $entityName
     * @param int $entityId
     * @param string|null $type
     * @return \App\Component\Image\Image[]
     */
    public function getImagesByEntityIdIndexedById(string $entityName, int $entityId, ?string $type): array
    {
        return $this->getImagesByEntityIdAndNameIndexedById($entityId, $entityName, $type);
    }

    /**
     * Supplier set items are created from all images (excluding main) whose description contains Product::SUPPLIER_SET_ITEM_NAME_COUNT_SEPARATOR
     *
     * @param \App\Model\Product\Product $product
     * @return \App\Component\Image\Image[]
     */
    public function getSupplierSetItemsImages(Product $product): array
    {
        $images = $this->getImagesByEntityIndexedById($product, null);
        array_shift($images);

        return array_filter($images, function ($image) {
            return $image->getDescription() !== null && strpos($image->getDescription(), Product::SUPPLIER_SET_ITEM_NAME_COUNT_SEPARATOR) !== false;
        });
    }

    /**
     * @param string|null $imageDescription
     * @return string
     */
    public function getSupplierSetItemName(?string $imageDescription): string
    {
        if ($imageDescription === null) {
            return '';
        }
        $separatorPosition = strpos($imageDescription, Product::SUPPLIER_SET_ITEM_NAME_COUNT_SEPARATOR);
        if ($separatorPosition === false) {
            return $imageDescription;
        }

        return substr($imageDescription, 0, $separatorPosition);
    }

    /**
     * @param string|null $imageDescription
     * @return int
     */
    public function getSupplierSetItemCount(?string $imageDescription): int
    {
        if ($imageDescription === null) {
            return 1;
        }
        $separatorPosition = strpos($imageDescription, Product::SUPPLIER_SET_ITEM_NAME_COUNT_SEPARATOR);
        if ($separatorPosition === false) {
            return 1;
        }

        return (int)substr($imageDescription, $separatorPosition + 1);
    }

    /**
     * @param object $entity
     * @param array $temporaryFilenames
     * @param string|null $type
     */
    protected function uploadImage($entity, $temporaryFilenames, $type): void
    {
        if (count($temporaryFilenames) > 0) {
            $entitiesForFlush = [];
            $imageEntityConfig = $this->imageConfig->getImageEntityConfig($entity);
            $entityName = $imageEntityConfig->getEntityName();
            $entityId = $this->getEntityId($entity);
            $oldImage = $this->imageRepository->findImageByEntity($entityName, $entityId, $type);

            if ($oldImage !== null) {
                $this->em->remove($oldImage);
                $entitiesForFlush[] = $oldImage;
            }

            $this->imageCacheFacade->invalidateCacheByEntityNameAndEntityIdAndType($entityName, $entityId, $type);

            $newImage = $this->imageFactory->create(
                $imageEntityConfig->getEntityName(),
                $entityId,
                $type,
                array_pop($temporaryFilenames)
            );
            $this->em->persist($newImage);
            $entitiesForFlush[] = $newImage;

            $this->em->flush($entitiesForFlush);
        }
    }

    /**
     * @param object $entity
     * @param array|null $temporaryFilenames
     * @param string|null $type
     */
    protected function uploadImages($entity, $temporaryFilenames, $type): void
    {
        if ($temporaryFilenames !== null && count($temporaryFilenames) > 0) {
            $imageEntityConfig = $this->imageConfig->getImageEntityConfig($entity);
            $entityName = $imageEntityConfig->getEntityName();
            $entityId = $this->getEntityId($entity);

            $images = $this->imageFactory->createMultiple($imageEntityConfig, $entityId, $type, $temporaryFilenames);
            foreach ($images as $image) {
                $this->em->persist($image);
            }

            $this->imageCacheFacade->invalidateCacheByEntityNameAndEntityIdAndType($entityName, $entityId, $type);

            $this->em->flush();
        }
    }

    /**
     * @param object $entity
     * @param \App\Component\Transfer\Pohoda\Product\Image\PohodaImage $pohodaImage
     * @param string $temporaryImagePath
     * @param string|null $type
     */
    public function uploadPohodaImage(object $entity, PohodaImage $pohodaImage, string $temporaryImagePath, ?string $type = null): void
    {
        $uploadedFile = new UploadedFile($temporaryImagePath, $pohodaImage->file);
        $temporaryFilename = $this->fileUpload->upload($uploadedFile);

        $imageEntityConfig = $this->imageConfig->getImageEntityConfig($entity);
        $entityName = $imageEntityConfig->getEntityName();
        $entityId = $this->getEntityId($entity);

        $image = $this->imageFactory->createFromPohodaImage($imageEntityConfig, $entityId, $temporaryFilename, $pohodaImage);

        $this->em->persist($image);

        $this->imageCacheFacade->invalidateCacheByEntityNameAndEntityIdAndType($entityName, $entityId, $type);

        $this->em->flush();
    }

    /**
     * @param object $entity
     * @param \App\Component\Image\Image[] $images
     */
    protected function deleteImages($entity, array $images): void
    {
        $entityName = $this->imageConfig->getEntityName($entity);
        $entityId = $this->getEntityId($entity);

        // files will be deleted in doctrine listener
        foreach ($images as $image) {
            $image->checkForDelete($entityName, $entityId);
        }

        foreach ($images as $image) {
            $imageToRemove = $this->imageRepository->findById($image->getId());
            $this->imageCacheFacade->invalidateCacheByEntityNameAndEntityIdAndType($entityName, $entityId, null);
            if ($imageToRemove !== null) {
                $this->em->remove($imageToRemove);
            }
        }
    }

    /**
     * @param object $entity
     * @param string|null $type
     * @return \App\Component\Image\Image
     */
    public function getImageByEntity($entity, $type): Image
    {
        $entityName = $this->imageConfig->getEntityName($entity);
        $entityId = $this->getEntityId($entity);
        $cachedImage = $this->imageCacheFacade->findCachedImageEntityByEntityNameAndEntityIdAndType($entityName, $entityId, $type);

        if ($cachedImage !== null) {
            return $cachedImage;
        }

        $image = $this->imageRepository->getImageByEntity(
            $entityName,
            $entityId,
            $type
        );

        $this->imageCacheFacade->setImageEntityIntoCacheByEntityNameAndEntityIdAndType($entityName, $entityId, $type, $image);

        return $image;
    }

    /**
     * @param object $entity
     * @param string|null $type
     * @return \App\Component\Image\Image[]
     */
    public function getImagesByEntityIndexedById($entity, $type): array
    {
        $entityName = $this->imageConfig->getEntityName($entity);
        $entityId = $this->getEntityId($entity);
        $cachedImages = $this->imageCacheFacade->findCachedImageEntitiesByEntityNameAndEntityIdAndType($entityName, $entityId, $type);

        if ($cachedImages !== null) {
            return $cachedImages;
        }

        $imagesByEntity = $this->imageRepository->getImagesByEntityIndexedById(
            $entityName,
            $entityId,
            $type
        );

        $this->imageCacheFacade->setImageEntitiesIntoCacheByEntityNameAndEntityIdAndType($entityName, $entityId, $type, $imagesByEntity);

        return $imagesByEntity;
    }

    /**
     * @param int $entityId
     * @param string $entityName
     * @param string|null $type
     * @return \App\Component\Image\Image[]
     */
    public function getImagesByEntityIdAndNameIndexedById(int $entityId, string $entityName, $type): array
    {
        $cachedImages = $this->imageCacheFacade->findCachedImageEntitiesByEntityNameAndEntityIdAndType($entityName, $entityId, $type);

        if ($cachedImages !== null) {
            return $cachedImages;
        }

        $imagesByEntity = $this->imageRepository->getImagesByEntityIndexedById(
            $entityName,
            $entityId,
            $type
        );

        $this->imageCacheFacade->setImageEntitiesIntoCacheByEntityNameAndEntityIdAndType($entityName, $entityId, $type, $imagesByEntity);

        return $imagesByEntity;
    }

    /**
     * @param object $entity
     * @param \Shopsys\FrameworkBundle\Component\FileUpload\ImageUploadData $imageUploadData
     * @param string|null $type
     * @param bool $renameProductImages
     * @param string[][] $productNames
     */
    public function manageImages(object $entity, ImageUploadData $imageUploadData, ?string $type = null, bool $renameProductImages = false, array $productNames = []): void
    {
        $entityName = $this->imageConfig->getEntityName($entity);
        $entityId = $this->getEntityId($entity);
        $this->imageCacheFacade->invalidateCacheByEntityNameAndEntityIdAndType($entityName, $entityId, $type);

        $imageEntityConfig = $this->imageConfig->getImageEntityConfig($entity);
        $uploadedFiles = $imageUploadData->uploadedFiles;
        /** @var \App\Component\Image\Image[] $orderedImages */
        $orderedImages = $imageUploadData->orderedImages;
        if ($renameProductImages) {
            $this->renameProductImages($orderedImages, $productNames);
        }

        if ($imageEntityConfig->isMultiple($type) === false) {
            if (count($orderedImages) > 1) {
                array_shift($orderedImages);
                $this->deleteImages($entity, $orderedImages);
            }
            $this->uploadImage($entity, $uploadedFiles, $type);
        } else {
            $this->saveImageOrdering($orderedImages);
            $this->uploadImages($entity, $uploadedFiles, $type);
        }

        /** @var \App\Component\Image\Image[] $imagesToDelete */
        $imagesToDelete = $imageUploadData->imagesToDelete;
        $this->deleteImages($entity, $imagesToDelete);
    }

    /**
     * @param array $entityIds
     * @param string $entityClass
     * @param string|null $type
     * @return \App\Component\Image\Image[]
     */
    public function getImagesByEntitiesIndexedByEntityId(array $entityIds, string $entityClass, ?string $type = null): array
    {
        $entityName = $this->imageConfig->getImageEntityConfigByClass($entityClass)->getEntityName();

        return $this->imageRepository->getMainImagesByEntitiesIndexedByEntityId($entityIds, $entityName, $type);
    }

    /**
     * @param string $entityName
     * @param int $entityId
     * @param string|null $type
     * @return \App\Component\Image\Image|null
     */
    public function findImageByEntity(string $entityName, int $entityId, ?string $type): ?Image
    {
        return $this->imageRepository->findImageByEntity($entityName, $entityId, $type);
    }

    /**
     * @param \App\Component\Image\Image[] $orderedImages
     */
    protected function saveImageOrdering($orderedImages): void
    {
        // Image entity can be cached and it caused fatal on flush ("Entity has to be managed or scheduled for removal for single computation")
        $persistedImages = [];
        foreach ($orderedImages as $image) {
            if ($this->em->getUnitOfWork()->isInIdentityMap($image) === true) {
                $persistedImages[] = $image;
            } else {
                try {
                    $persistedImages[] = $this->getById($image->getId());
                } catch (ImageNotFoundException $ex) {
                }
            }
        }
        parent::saveImageOrdering($persistedImages);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param int $id
     * @param string $extension
     * @param string $entityName
     * @param string|null $type
     * @param string|null $sizeName
     * @param int|null $imageEntityId
     * @return string
     */
    public function getImageUrlFromAttributes(
        DomainConfig $domainConfig,
        int $id,
        string $extension,
        string $entityName,
        ?string $type,
        ?string $sizeName = null,
        ?int $imageEntityId = null
    ): string {
        $imageFilepath = $this->imageLocator->getRelativeImageFilepathFromAttributes($id, $extension, $entityName, $type, $sizeName, null, $imageEntityId);

        $imageUrl = $domainConfig->getUrl() . $this->imageUrlPrefix . $imageFilepath;

        return $this->replaceDomainUrlByCdnDomain($imageUrl, $domainConfig);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param int $id
     * @param string $extension
     * @param string $entityName
     * @param string|null $type
     * @param string|null $sizeName
     * @return \Shopsys\FrameworkBundle\Component\Image\AdditionalImageData[]
     */
    public function getAdditionalImagesDataFromAttributes(
        DomainConfig $domainConfig,
        int $id,
        string $extension,
        string $entityName,
        ?string $type,
        ?string $sizeName = null
    ): array {
        $entityConfig = $this->imageConfig->getEntityConfigByEntityName($entityName);
        $sizeConfig = $entityConfig->getSizeConfigByType($type, $sizeName);

        $result = [];
        foreach ($sizeConfig->getAdditionalSizes() as $additionalSizeIndex => $additionalSizeConfig) {
            $imageFilepath = $this->imageLocator->getRelativeImageFilepathFromAttributes($id, $extension, $entityName, $type, $sizeName, $additionalSizeIndex);
            $url = $domainConfig->getUrl() . $this->imageUrlPrefix . $imageFilepath;

            $result[] = new AdditionalImageData($additionalSizeConfig->getMedia(), $this->replaceDomainUrlByCdnDomain($url, $domainConfig));
        }

        return $result;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param \App\Component\Image\Image|object $imageOrEntity
     * @param string|null $sizeName
     * @param string|null $type
     * @return string
     */
    public function getImageUrl(DomainConfig $domainConfig, $imageOrEntity, $sizeName = null, $type = null): string
    {
        $locale = $domainConfig->getLocale();
        $image = $this->getImageByObject($imageOrEntity, $type);
        if ($this->imageLocator->imageExists($image, $locale)) {
            $imageUrl = $domainConfig->getUrl()
                . $this->imageUrlPrefix
                . $this->imageLocator->getRelativeImageFilepath($image, $sizeName, $locale);

            return $this->replaceDomainUrlByCdnDomain($imageUrl, $domainConfig);
        }

        throw new ImageNotFoundException();
    }

    /**
     * @param \App\Component\Image\Image[] $orderedImages
     * @param string[][] $productNames
     */
    private function renameProductImages(array $orderedImages, array $productNames = []): void
    {
        if (count($productNames) === 0) {
            return;
        }

        foreach ($productNames as $productName) {
            if ($productName['new'] !== $productName['old']) {
                foreach ($orderedImages as $orderedImage) {
                    $newImageName = $this->imageLocator->getProductFilenamePartByProductName($productName['new'], $orderedImage->getId(), $orderedImage->getExtension());
                    $oldImageName = $this->imageLocator->getProductFilenamePartByProductName($productName['old'], $orderedImage->getId(), $orderedImage->getExtension());
                    $this->renameImages($newImageName, $oldImageName);
                }
            }
        }

        $productNamesForDeletion = $this->productNamesToFriendlyUrlSlug($productNames);
        $this->deleteUnusedImages($productNamesForDeletion, $orderedImages);
    }

    /**
     * @param array $productNames
     * @return array
     */
    private function productNamesToFriendlyUrlSlug(array $productNames): array
    {
        foreach ($productNames as $locale => $productName) {
            if ($productName['old'] !== null) {
                $productNames[$locale]['old'] = TransformString::stringToFriendlyUrlSlug($productName['old']);
            }
            if ($productName['new'] !== null) {
                $productNames[$locale]['new'] = TransformString::stringToFriendlyUrlSlug($productName['new']);
            }
        }

        return $productNames;
    }

    /**
     * This method delete product images that dont match with product names anymore. Conditions check these situations:
     * old product names are same, new product names are different and user renamed product for both domains - in this case delete
     * old product names are same, new product names are different but user renamed product for only one domain - in this case dont delete
     * old product names are different, new product names are same and user renamed product for both domains - in this case delete
     * old product names are different, new product names are same but user renamed product for only one domain - in this case dont delete
     * old product names are same, new product names are same and user renamed product for both domains - in this case delete
     * old product names are different, new product names are different and user renamed product for both domains - in this case delete
     *
     * @param array $productNames
     * @param \App\Component\Image\Image[] $orderedImages
     */
    private function deleteUnusedImages(array $productNames, array $orderedImages): void
    {
        foreach ($productNames as $productName) {
            $sameOldNames = true;
            $sameNewNames = true;
            $onlyOneRenamed = $productName['old'] === $productName['new'];
            foreach ($productNames as $innerProductName) {
                if ($productName['old'] !== $innerProductName['old']) {
                    $sameOldNames = false;
                }
                if ($productName['new'] !== $innerProductName['new']) {
                    $sameNewNames = false;
                }
                if ($onlyOneRenamed) {
                    continue;
                }

                if ($productName['old'] === $innerProductName['new']) {
                    $onlyOneRenamed = true;
                }
            }

            if (($sameOldNames && $sameNewNames && $onlyOneRenamed) || (!$sameOldNames && !$sameNewNames && $onlyOneRenamed)) {
                continue;
            }

            if ((!$sameOldNames || $sameNewNames || $onlyOneRenamed) && ($sameOldNames || !$sameNewNames || $onlyOneRenamed) && (!$sameOldNames || !$sameNewNames) && ($sameOldNames || $sameNewNames)) {
                continue;
            }

            foreach ($orderedImages as $orderedImage) {
                $imageNameToDelete = $this->imageLocator->getProductFilenamePartByProductName($productName['old'], $orderedImage->getId(), $orderedImage->getExtension());
                $this->removeImageFiles($imageNameToDelete);
            }
        }
    }

    /**
     * @param string $filename
     */
    private function removeImageFiles(string $filename): void
    {
        $productImageDirs = $this->filesystem->listContents($this->productImageDir);
        foreach ($productImageDirs as $productImageDir) {
            $imageDir = $productImageDir['path'];
            $imagesInDir = $this->filesystem->listContents($imageDir);

            foreach ($imagesInDir as $imageInDir) {
                if ($imageInDir['basename'] === $filename) {
                    $this->filesystem->delete($imageInDir['dirname'] . DIRECTORY_SEPARATOR . $filename);
                }
            }
        }
    }

    /**
     * @param string $newImageName
     * @param string $oldImageName
     */
    private function renameImages(string $newImageName, string $oldImageName): void
    {
        $productImageDirs = $this->filesystem->listContents($this->productImageDir);
        foreach ($productImageDirs as $productImageDir) {
            $imageDir = $productImageDir['path'];
            $imagesInDir = $this->filesystem->listContents($imageDir);

            foreach ($imagesInDir as $imageInDir) {
                if ($imageInDir['basename'] === $oldImageName) {
                    if ($this->filesystem->has($imageInDir['path'])) {
                        try {
                            $this->filesystem->copy($imageInDir['path'], $imageInDir['dirname'] . DIRECTORY_SEPARATOR . $newImageName);
                        } catch (FileExistsException $e) {
                            continue;
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $entityName
     * @param string|null $type
     * @param int|null $imageIdFrom
     * @return \App\Component\Image\Image[]
     */
    public function getImagesByEntityNameAndTypeOrderedById(string $entityName, ?string $type = null, ?int $imageIdFrom = null): array
    {
        return $this->imageRepository->getImagesByEntityNameAndTypeOrderedById($entityName, $type, $imageIdFrom);
    }

    /**
     * @param \App\Component\Image\Image $image
     */
    public function deleteImageFiles(Image $image)
    {
        $entityName = $image->getEntityName();
        $imageConfig = $this->imageConfig->getEntityConfigByEntityName($entityName);
        $sizeConfigs = $image->getType() === null ? $imageConfig->getSizeConfigs() : $imageConfig->getSizeConfigsByType($image->getType());

        foreach ($sizeConfigs as $sizeConfig) {
            foreach (DomainHelper::LOCALES as $locale) {
                $filepath = $this->imageLocator->getAbsoluteImageFilepath($image, $sizeConfig->getName(), $locale);

                if ($this->filesystem->has($filepath)) {
                    $this->filesystem->delete($filepath);
                }
            }
        }
    }
}
