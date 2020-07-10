<?php

declare(strict_types=1);

namespace App\Twig;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\UploadedFile\Config\UploadedFileTypeConfig;
use Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileFacade;
use Shopsys\FrameworkBundle\Twig\FileThumbnail\FileThumbnailExtension;
use Shopsys\FrameworkBundle\Twig\UploadedFileExtension as BaseUploadedFileExtensionAlias;
use Twig\TwigFunction;

class UploadedFileExtension extends BaseUploadedFileExtensionAlias
{
    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileFacade $uploadedFileFacade
     * @param \Shopsys\FrameworkBundle\Twig\FileThumbnail\FileThumbnailExtension $fileThumbnailExtension
     */
    public function __construct(
        Domain $domain,
        UploadedFileFacade $uploadedFileFacade,
        FileThumbnailExtension $fileThumbnailExtension
    ) {
        parent::__construct($domain, $uploadedFileFacade, $fileThumbnailExtension);
    }

    /**
     * @return \Twig\TwigFunction[]
     */
    public function getFunctions(): array
    {
        $parentFunctions = parent::getFunctions();

        return array_merge(
            [
                new TwigFunction('uploadedFiles', [$this, 'getUploadedFilesByEntity']),
            ],
            $parentFunctions
        );
    }

    /**
     * @param object $entity
     * @param string $type
     * @return \Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFile[]
     */
    public function getUploadedFilesByEntity(
        object $entity,
        string $type = UploadedFileTypeConfig::DEFAULT_TYPE_NAME
    ): array {
        return $this->uploadedFileFacade->getUploadedFilesByEntity($entity, $type);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'file_extension';
    }
}
