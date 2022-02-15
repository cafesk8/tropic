<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Component\FileUpload\FileNamingConvention;
use App\Component\Image\ImageRepository;
use App\Model\Product\ProductFacade;
use League\Flysystem\FilesystemInterface;
use Shopsys\FrameworkBundle\Component\Image\Config\ImageConfig;
use Shopsys\FrameworkBundle\Component\Image\Exception\ImageException;
use Shopsys\FrameworkBundle\Component\Image\Processing\ImageGeneratorFacade;
use Shopsys\FrameworkBundle\Component\String\TransformString;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImageController extends FrontBaseController
{
    /**
     * @var \App\Component\Image\Processing\ImageGeneratorFacade
     */
    private $imageGeneratorFacade;

    /**
     * @var \League\Flysystem\FilesystemInterface
     */
    private $filesystem;

    private ProductFacade $productFacade;

    private ImageRepository $imageRepository;

    /**
     * @param \App\Component\Image\Processing\ImageGeneratorFacade $imageGeneratorFacade
     * @param \League\Flysystem\FilesystemInterface $filesystem
     * @param \App\Component\Image\ImageRepository $imageRepository
     * @param \App\Model\Product\ProductFacade $productFacade
     */
    public function __construct(
        ImageGeneratorFacade $imageGeneratorFacade,
        FilesystemInterface $filesystem,
        ImageRepository $imageRepository,
        ProductFacade $productFacade
    ) {
        $this->imageGeneratorFacade = $imageGeneratorFacade;
        $this->filesystem = $filesystem;
        $this->productFacade = $productFacade;
        $this->imageRepository = $imageRepository;
    }

    /**
     * @param string $entityName
     * @param string|null $type
     * @param string|null $sizeName
     * @param int|string $imageId
     * @param string $extension
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function getImageAction($entityName, $type, $sizeName, $imageId, $extension)
    {
        if ($sizeName === ImageConfig::DEFAULT_SIZE_NAME) {
            $sizeName = null;
        }

        try {
            if ($entityName === FileNamingConvention::PRODUCT_CLASS_NAME && is_numeric($imageId)) {
                $image = $this->imageRepository->getById($imageId);
                $product = $this->productFacade->getById($image->getEntityId());
                $newImageId = TransformString::stringToFriendlyUrlSlug($product->getName()) . '_' . $imageId;

                if ($type === null) {
                    return $this->redirectToRoute('front_image_without_type', [
                        'entityName' => $entityName,
                        'sizeName' => $sizeName,
                        'imageId' => $newImageId,
                        'extension' => $extension,
                    ]);
                } else {
                    return $this->redirectToRoute('front_image', [
                        'entityName' => $entityName,
                        'type' => $type,
                        'sizeName' => $sizeName,
                        'imageId' => $newImageId,
                        'extension' => $extension,
                    ]);
                }
            }

            $imageFilepath = $this->imageGeneratorFacade->generateImageAndGetFilepath($entityName, $imageId, $type, $sizeName);
        } catch (ImageException $e) {
            $message = sprintf(
                'Generate image for entity "%s" (type=%s, size=%s, imageId=%s) failed',
                $entityName,
                $type,
                $sizeName,
                $imageId
            );
            throw $this->createNotFoundException($message, $e);
        }

        return $this->sendImage($imageFilepath);
    }

    /**
     * @param mixed $entityName
     * @param mixed $type
     * @param mixed $sizeName
     * @param int $imageId
     * @param int $additionalIndex
     */
    public function getAdditionalImageAction($entityName, $type, $sizeName, int $imageId, int $additionalIndex)
    {
        if ($sizeName === ImageConfig::DEFAULT_SIZE_NAME) {
            $sizeName = null;
        }

        try {
            $imageFilepath = $this->imageGeneratorFacade->generateAdditionalImageAndGetFilepath($entityName, $imageId, $additionalIndex, $type, $sizeName);
        } catch (ImageException $e) {
            $message = sprintf(
                'Generate image for entity "%s" (type=%s, size=%s, imageId=%s, additionalIndex=%s) failed',
                $entityName,
                $type,
                $sizeName,
                $imageId,
                $additionalIndex
            );
            throw $this->createNotFoundException($message, $e);
        }

        return $this->sendImage($imageFilepath);
    }

    /**
     * @param string $imageFilepath
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    protected function sendImage(string $imageFilepath): StreamedResponse
    {
        try {
            $fileStream = $this->filesystem->readStream($imageFilepath);
            $headers = [
                'content-type' => $this->filesystem->getMimetype($imageFilepath),
                'content-size' => $this->filesystem->getSize($imageFilepath),
                'Access-Control-Allow-Origin' => '*',
            ];

            $callback = function () use ($fileStream) {
                $out = fopen('php://output', 'wb');
                stream_copy_to_stream($fileStream, $out);
            };

            return new StreamedResponse($callback, 200, $headers);
        } catch (\Exception $e) {
            $message = 'Response with file "' . $imageFilepath . '" failed.';
            throw $this->createNotFoundException($message, $e);
        }
    }
}
