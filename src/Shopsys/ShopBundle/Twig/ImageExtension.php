<?php

namespace Shopsys\ShopBundle\Twig;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Component\Image\ImageLocator;
use Shopsys\FrameworkBundle\Twig\ImageExtension as BaseImageExtension;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Asset\Package;
use Twig\TwigFunction;

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
     * @param \Shopsys\FrameworkBundle\Component\Image\ImageFacade $imageFacade
     * @param \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $templating
     */
    public function __construct($frontDesignImageUrlPrefix, Package $assetsPackage, Domain $domain, ImageLocator $imageLocator, ImageFacade $imageFacade, EngineInterface $templating)
    {
        parent::__construct($frontDesignImageUrlPrefix, $domain, $imageLocator, $imageFacade, $templating);
        $this->assetsPackage = $assetsPackage;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return array_merge(parent::getFunctions(), [
            new TwigFunction('imagePlaceholder', [$this, 'getImagePlaceholder']),
        ]);
    }

    public function getImagePlaceholder()
    {
        return $this->assetsPackage->getUrl('assets/frontend/images/design/placeholder.gif');
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Image\Image|Object $imageOrEntity
     * @param array $attributes
     * @return string
     */
    public function getImageHtml($imageOrEntity, array $attributes = [])
    {
        $this->preventDefault($attributes);

        try {
            $image = $this->imageFacade->getImageByObject($imageOrEntity, $attributes['type']);
            $entityName = $image->getEntityName();
            $attributes['src'] = $this->getImagePlaceholder();
            $attributes['data-original'] = $this->getImageUrl($image, $attributes['size'], $attributes['type']);

            if (isset($attributes['class'])) {
                $attributes['class'] .= ' js-lazy-load';
            } else {
                $attributes['class'] = 'js-lazy-load';
            }

            $additionalImagesData = $this->imageFacade->getAdditionalImagesData($this->domain->getCurrentDomainConfig(), $image, $attributes['size'], $attributes['type']);
        } catch (\Shopsys\FrameworkBundle\Component\Image\Exception\ImageNotFoundException $e) {
            $entityName = 'noimage';
            $attributes['src'] = $this->getEmptyImageUrl();
            $additionalImagesData = [];
        }

        return $this->getImageHtmlByEntityName($attributes, $entityName, $additionalImagesData);
    }
}
