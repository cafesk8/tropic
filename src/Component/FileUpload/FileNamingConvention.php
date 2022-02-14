<?php

declare(strict_types=1);

namespace App\Component\FileUpload;

use Shopsys\FrameworkBundle\Component\FileUpload\Exception\UnresolvedNamingConventionException;
use Shopsys\FrameworkBundle\Component\FileUpload\FileNamingConvention as BaseFileNamingConvention;
use Shopsys\FrameworkBundle\Component\String\TransformString;

class FileNamingConvention extends BaseFileNamingConvention
{
    public const PRODUCT_CLASS_NAME = 'product';

    private FileNamingConventionRepository $fileNamingConventionRepository;

    /**
     * @param \App\Component\FileUpload\FileNamingConventionRepository $fileNamingConventionRepository
     */
    public function __construct(FileNamingConventionRepository $fileNamingConventionRepository)
    {
        $this->fileNamingConventionRepository = $fileNamingConventionRepository;
    }

    /**
     * @param int $namingConventionType
     * @param string $originalFilename
     * @param int|null $entityId
     * @param string|null $entityName
     * @param string|null $entityType
     * @param int|null $imageEntityId
     * @param string|null $locale
     * @param bool $isImage
     * @return string
     */
    public function getFilenameByNamingConvention(
        $namingConventionType,
        $originalFilename,
        $entityId = null,
        ?string $entityName = null,
        ?string $entityType = null,
        ?int $imageEntityId = null,
        ?string $locale = null,
        bool $isImage = false
    ): string {
        if ($entityName === self::PRODUCT_CLASS_NAME && $entityType === null && $imageEntityId !== null && is_int($entityId) && $locale !== null && $isImage) {
            $product = $this->fileNamingConventionRepository->getProductById($imageEntityId);
            $productEscapedName = TransformString::stringToFriendlyUrlSlug($product->getName($locale));

            return $productEscapedName . '_' . $entityId . '.' . pathinfo($originalFilename, PATHINFO_EXTENSION);
        }

        if ($namingConventionType === self::TYPE_ID && is_int($entityId)) {
            return $entityId . '.' . pathinfo($originalFilename, PATHINFO_EXTENSION);
        }

        if ($namingConventionType === static::TYPE_ORIGINAL_NAME) {
            return $originalFilename;
        }

        $message = 'Naming convention ' . $namingConventionType . ' cannot by resolved to filename';
        throw new UnresolvedNamingConventionException($message);
    }
}
