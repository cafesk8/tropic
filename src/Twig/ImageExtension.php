<?php

declare(strict_types=1);

namespace App\Twig;

use App\Model\Product\Product;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Component\Image\ImageLocator;
use Shopsys\ReadModelBundle\Twig\ImageExtension as BaseImageExtension;
use Symfony\Component\Asset\Package;
use Twig\Environment;
use Twig\TwigFunction;

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
     * @param \Twig\Environment $twigEnvironment
     * @param bool $isLazyLoadEnabled
     */
    public function __construct(
        $frontDesignImageUrlPrefix,
        Package $assetsPackage,
        Domain $domain,
        ImageLocator $imageLocator,
        ImageFacade $imageFacade,
        Environment $twigEnvironment,
        bool $isLazyLoadEnabled
    ) {
        parent::__construct($frontDesignImageUrlPrefix, $domain, $imageLocator, $imageFacade, $twigEnvironment, $isLazyLoadEnabled);
        $this->assetsPackage = $assetsPackage;
    }

    /**
     * @return \Twig\TwigFunction[]
     */
    public function getFunctions()
    {
        $functions = parent::getFunctions();
        $functions[] = new TwigFunction('getSupplierSetImagesExcludingMain', [$this, 'getSupplierSetImagesExcludingMain']);
        $functions[] = new TwigFunction('getSupplierSetItemName', [$this, 'getSupplierSetItemName']);
        $functions[] = new TwigFunction('getSupplierSetItemCount', [$this, 'getSupplierSetItemCount']);

        return $functions;
    }

    private function getImagePlaceholder()
    {
        return $this->assetsPackage->getUrl('public/frontend/images/design/placeholder.gif');
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

        return $this->twigEnvironment->render('@ShopsysFramework/Common/image.html.twig', [
            'attr' => $htmlAttributes,
            'additionalImagesData' => $additionalImagesData,
            'imageCssClass' => $this->getImageCssClass($entityName, $attributes['type'], $attributes['size']),
        ]);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Component\Image\Image[]
     */
    public function getSupplierSetImagesExcludingMain(Product $product)
    {
        $images = $this->getImages($product, null);
        array_shift($images);

        return $images;
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
}
