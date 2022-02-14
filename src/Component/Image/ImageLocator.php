<?php

declare(strict_types=1);

namespace App\Component\Image;

use App\Component\FileUpload\FileNamingConvention;
use App\Model\Product\Product;
use App\Model\Product\ProductRepository;
use League\Flysystem\FilesystemInterface;
use Shopsys\FrameworkBundle\Component\Image\Config\ImageConfig;
use Shopsys\FrameworkBundle\Component\Image\Image;
use Shopsys\FrameworkBundle\Component\Image\ImageLocator as BaseImageLocator;
use Shopsys\FrameworkBundle\Component\String\TransformString;

/**
 * @method string getRelativeAdditionalImageFilepath(\App\Component\Image\Image $image, int $additionalIndex, string|null $sizeName)
 * @method string getAbsoluteAdditionalImageFilepath(\App\Component\Image\Image $image, int $additionalIndex, string|null $sizeName)
 */
class ImageLocator extends BaseImageLocator
{
    private ProductRepository $productRepository;

    /**
     * @param mixed $imageDir
     * @param \Shopsys\FrameworkBundle\Component\Image\Config\ImageConfig $imageConfig
     * @param \League\Flysystem\FilesystemInterface $filesystem
     * @param \App\Model\Product\ProductRepository $productRepository
     */
    public function __construct($imageDir, ImageConfig $imageConfig, FilesystemInterface $filesystem, ProductRepository $productRepository)
    {
        parent::__construct($imageDir, $imageConfig, $filesystem);

        $this->productRepository = $productRepository;
    }

    /**
     * @param \App\Component\Image\Image $image
     * @param string|null $sizeName
     * @param string|null $locale
     * @return string
     */
    public function getRelativeImageFilepath(Image $image, $sizeName, ?string $locale = null): string
    {
        $path = $this->getRelativeImagePath($image->getEntityName(), $image->getType(), $sizeName);

        if ($image->getEntityName() === FileNamingConvention::PRODUCT_CLASS_NAME && $image->getType() === null) {
            return $this->getProductFilenamePart($image->getEntityId(), $image->getId(), $image->getExtension(), $path, $locale);
        }

        return $path . $image->getFilename();
    }

    /**
     * @param \App\Component\Image\Image $image
     * @param string|null $sizeName
     * @param string|null $locale
     * @return string
     */
    public function getAbsoluteImageFilepath(Image $image, $sizeName, ?string $locale = null): string
    {
        $relativePath = $this->getRelativeImageFilepath($image, $sizeName, $locale);

        return $this->imageDir . $relativePath;
    }

    /**
     * @param int $id
     * @param string $extension
     * @param string $entityName
     * @param string|null $type
     * @param string|null $sizeName
     * @param int|null $additionalIndex
     * @param int|null $imageEntityId
     * @return string
     */
    public function getRelativeImageFilepathFromAttributes(
        int $id,
        string $extension,
        string $entityName,
        ?string $type,
        ?string $sizeName = null,
        ?int $additionalIndex = null,
        ?int $imageEntityId = null
    ): string {
        $path = $this->getRelativeImagePath($entityName, $type, $sizeName);
        $filenameForAdditional = $id . '.' . $extension;

        if ($entityName === FileNamingConvention::PRODUCT_CLASS_NAME && $imageEntityId !== null && $type !== Product::IMAGE_TYPE_STICKER) {
            $filename = $this->getProductFilenamePart($imageEntityId, $id, $extension);
        } else {
            $filename = $id . '.' . $extension;
        }

        if ($additionalIndex !== null) {
            $filename = $this->getAdditionalImageFilename($filenameForAdditional, $additionalIndex);
        }

        return $path . $filename;
    }

    /**
     * @param int $productId
     * @param int $imageId
     * @param string $imageExtension
     * @param string|null $path
     * @param string|null $locale
     * @return string
     */
    public function getProductFilenamePart(int $productId, int $imageId, string $imageExtension, ?string $path = null, ?string $locale = null): string
    {
        $product = $this->productRepository->getById($productId);
        $filename = $this->getProductFilenamePartByProductName($product->getName($locale), $imageId, $imageExtension);

        return $path === null ? $filename : $path . $filename;
    }

    /**
     * @param string|null $productName
     * @param int $imageId
     * @param string $imageExtension
     * @return string
     */
    public function getProductFilenamePartByProductName(?string $productName, int $imageId, string $imageExtension): string
    {
        $productEscapedName = TransformString::stringToFriendlyUrlSlug($productName);

        return $productEscapedName . '_' . $imageId . '.' . $imageExtension;
    }

    /**
     * @param \App\Component\Image\Image $image
     * @param string|null $locale
     * @return bool
     */
    public function imageExists(Image $image, ?string $locale = null): bool
    {
        $imageFilepath = $this->getAbsoluteImageFilepath($image, ImageConfig::ORIGINAL_SIZE_NAME, $locale);

        return $this->filesystem->has($imageFilepath);
    }
}
