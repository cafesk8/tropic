<?php

declare(strict_types=1);

namespace App\Twig;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Component\Image\ImageLocator;
use Shopsys\ReadModelBundle\Twig\ImageExtension as BaseImageExtension;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Asset\Package;

/**
 * @property \App\Component\Image\ImageFacade $imageFacade
 * @method bool imageExists(\App\Component\Image\Image|object $imageOrEntity, string|null $type)
 * @method string getImageUrl(\App\Component\Image\Image|object $imageOrEntity, string|null $sizeName, string|null $type)
 * @method \App\Component\Image\Image[] getImages(object $entity, string|null $type)
 * @method string getImageHtml(\App\Component\Image\Image|object $imageOrEntity, array $attributes)
 */
class ImageExtension extends BaseImageExtension
{
    /**
     * @var \Symfony\Component\Asset\Package
     */
    private $assetsPackage;

    /**
     * @param mixed $frontDesignImageUrlPrefix
     * @param \Symfony\Component\Asset\Package $assetsPackage
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Component\Image\ImageLocator $imageLocator
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $templating
     * @param bool $isLazyLoadEnabled
     */
    public function __construct(
        $frontDesignImageUrlPrefix,
        Package $assetsPackage,
        Domain $domain,
        ImageLocator $imageLocator,
        ImageFacade $imageFacade,
        EngineInterface $templating,
        bool $isLazyLoadEnabled
    ) {
        parent::__construct($frontDesignImageUrlPrefix, $domain, $imageLocator, $imageFacade, $templating, $isLazyLoadEnabled);
        $this->assetsPackage = $assetsPackage;
    }

    private function getImagePlaceholder()
    {
        return $this->assetsPackage->getUrl('assets/frontend/images/design/placeholder.gif');
    }

    /**
     * Copy-pasted from ImageExtension from FrameworkBundle
     * Just the placeholder is used for $htmlAttributes['src'] when using lazy-load
     *
     * @param array $attributes
     * @param string $entityName
     * @param \Shopsys\FrameworkBundle\Component\Image\AdditionalImageData[] $additionalImagesData
     * @return string
     */
    protected function getImageHtmlByEntityName(array $attributes, $entityName, $additionalImagesData = []): string
    {
        $htmlAttributes = $attributes;
        unset($htmlAttributes['type'], $htmlAttributes['size']);

        $useLazyLoading = array_key_exists('lazy', $attributes) ? (bool)$attributes['lazy'] : true;
        unset($htmlAttributes['lazy']);

        if ($useLazyLoading === true) {
            $htmlAttributes['loading'] = 'lazy';
            $htmlAttributes['data-src'] = $htmlAttributes['src'];
            $htmlAttributes['src'] = $this->getImagePlaceholder();
        }

        return $this->templating->render('@ShopsysFramework/Common/image.html.twig', [
            'attr' => $htmlAttributes,
            'additionalImagesData' => $additionalImagesData,
            'imageCssClass' => $this->getImageCssClass($entityName, $attributes['type'], $attributes['size']),
        ]);
    }
}