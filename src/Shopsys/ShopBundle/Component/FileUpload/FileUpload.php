<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Component\FileUpload;

use Shopsys\FrameworkBundle\Component\FileUpload\FileUpload as BaseFileUpload;

class FileUpload extends BaseFileUpload
{
    /**
     * @param string $filename
     * @param bool $isImage
     * @param string $category
     * @param string|null $targetDirectory
     * @return string
     */
    public function getTargetFilepathForMigration(string $filename, bool $isImage, string $category, ?string $targetDirectory = null)
    {
        return $this->getTargetFilepath($filename, $isImage, $category, $targetDirectory);
    }
}
