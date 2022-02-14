<?php

declare(strict_types=1);

namespace App\Twig;

use App\Component\Image\Image;
use App\Model\Image\ImageView;
use App\Model\Image\ImageViewFactory;
use App\Model\Product\Brand\Brand;
use App\Model\Product\Product;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\Config\ImageConfig;
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
 * @property \App\Component\Image\ImageLocator $imageLocator
 */
class ImageExtension extends BaseImageExtension
{
    private Package $assetsPackage;

    private ImageConfig $imageConfig;

    private ImageViewFactory $imageViewFactory;

    /**
     * @param mixed $frontDesignImageUrlPrefix
     * @param \Symfony\Component\Asset\Package $assetsPackage
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Component\Image\ImageLocator $imageLocator
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \Twig\Environment $twigEnvironment
     * @param bool $isLazyLoadEnabled
     * @param \Shopsys\FrameworkBundle\Component\Image\Config\ImageConfig $imageConfig
     * @param \App\Model\Image\ImageViewFactory $imageViewFactory
     */
    public function __construct(
        $frontDesignImageUrlPrefix,
        Package $assetsPackage,
        Domain $domain,
        ImageLocator $imageLocator,
        ImageFacade $imageFacade,
        Environment $twigEnvironment,
        bool $isLazyLoadEnabled,
        ImageConfig $imageConfig,
        ImageViewFactory $imageViewFactory
    ) {
        parent::__construct($frontDesignImageUrlPrefix, $domain, $imageLocator, $imageFacade, $twigEnvironment, $isLazyLoadEnabled);
        $this->assetsPackage = $assetsPackage;
        $this->imageConfig = $imageConfig;
        $this->imageViewFactory = $imageViewFactory;
    }

    /**
     * @return \Twig\TwigFunction[]
     */
    public function getFunctions(): array
    {
        $functions = parent::getFunctions();
        $functions[] = new TwigFunction('getSupplierSetItemsImages', [$this, 'getSupplierSetItemsImages']);
        $functions[] = new TwigFunction('getSupplierSetItemName', [$this, 'getSupplierSetItemName']);
        $functions[] = new TwigFunction('getSupplierSetItemCount', [$this, 'getSupplierSetItemCount']);
        $functions[] = new TwigFunction('getProductSetImages', [$this, 'getProductSetImages']);
        $functions[] = new TwigFunction('shouldVariantImageBeDisplayed', [$this, 'shouldVariantImageBeDisplayed']);
        $functions[] = new TwigFunction('getImageViews', [$this, 'getImageViews']);

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

        $useLazyLoading = !array_key_exists('lazy', $attributes) || (bool)$attributes['lazy'];
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
    public function getSupplierSetItemsImages(Product $product)
    {
        return $this->imageFacade->getSupplierSetItemsImages($product);
    }

    /**
     * @param string|null $imageDescription
     * @return string
     */
    public function getSupplierSetItemName(?string $imageDescription): string
    {
        return $this->imageFacade->getSupplierSetItemName($imageDescription);
    }

    /**
     * @param string|null $imageDescription
     * @return int
     */
    public function getSupplierSetItemCount(?string $imageDescription): int
    {
        return $this->imageFacade->getSupplierSetItemCount($imageDescription);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Component\Image\Image[]
     */
    public function getProductSetImages(Product $product): array
    {
        $setImages = [];
        if ($product->isPohodaProductTypeSet()) {
            foreach ($product->getProductSets() as $productSet) {
                try {
                    $images = $this->imageFacade->getImagesByEntityIndexedById($productSet->getItem(), null);
                    $setImages = array_merge($setImages, $images);
                } catch (\Shopsys\FrameworkBundle\Component\Image\Exception\ImageNotFoundException $e) {
                    continue;
                }
            }
        } elseif ($product->isSupplierSet()) {
            $setImages = $this->getImages($product, null);
            array_shift($setImages);
        }

        return $setImages;
    }

    /**
     * On FE product detail, the variant image is displayed only when it differs from the main variant image
     *
     * @param \App\Model\Product\Product $variant
     * @return bool
     */
    public function shouldVariantImageBeDisplayed(Product $variant): bool
    {
        $variantImage = $this->imageFacade->findImageByEntity('product', $variant->getId(), null);
        if ($variantImage === null) {
            return false;
        }
        $mainVariantImage = $this->imageFacade->findImageByEntity('product', $variant->getMainVariant()->getId(), null);
        if ($mainVariantImage !== null && $variantImage->getDescription() !== $mainVariantImage->getDescription() || $mainVariantImage === null) {
            return true;
        }

        return false;
    }

    /**
     * @param Object|\App\Component\Image\Image|\Shopsys\ReadModelBundle\Image\ImageView|null $imageOrEntity
     * @param array $attributes
     * @return string
     */
    public function getImageHtml($imageOrEntity, array $attributes = []): string
    {
        if ($imageOrEntity instanceof ImageView) {
            $this->preventDefault($attributes);

            $entityName = $imageOrEntity->getEntityName();

            $attributes['src'] = $this->imageFacade->getImageUrlFromAttributes(
                $this->domain->getCurrentDomainConfig(),
                $imageOrEntity->getId(),
                $imageOrEntity->getExtension(),
                $entityName,
                $imageOrEntity->getType(),
                $attributes['size'],
                $imageOrEntity->getEntityId()
            );

            $additionalImagesData = $this->imageFacade->getAdditionalImagesDataFromAttributes(
                $this->domain->getCurrentDomainConfig(),
                $imageOrEntity->getId(),
                $imageOrEntity->getExtension(),
                $entityName,
                $imageOrEntity->getType(),
                $attributes['size']
            );

            return $this->getImageHtmlByEntityName($attributes, $entityName, $additionalImagesData);
        }

        if ($imageOrEntity instanceof Product) {
            return $this->getProductImageHtml($imageOrEntity, $attributes);
        }

        if ($imageOrEntity instanceof Brand) {
            $this->preventDefault($attributes);
            $brandImage = $this->imageFacade->findImageByEntity(
                $this->imageConfig->getEntityName($imageOrEntity),
                $imageOrEntity->getId(),
                $attributes['type']
            );

            if ($brandImage === null) {
                return '';
            }
        }

        return parent::getImageHtml($imageOrEntity, $attributes);
    }

    /**
     * @param object $entity
     * @param string|null $type
     * @return \App\Model\Image\ImageView[]
     */
    public function getImageViews(object $entity, ?string $type = null): array
    {
        return array_map(
            fn (Image $image) => $this->imageViewFactory->createFromImage($image),
            $this->getImages($entity, $type)
        );
    }

    /**
     * @param Object|\App\Component\Image\Image|\Shopsys\ReadModelBundle\Image\ImageView|null $imageOrEntity
     * @param array $attributes
     * @return string
     */
    public function getProductImageHtml($imageOrEntity, array $attributes = []): string
    {
        $this->preventDefault($attributes);
        $attributes['title'] = $imageOrEntity->getName();
        $attributes['alt'] = $imageOrEntity->getName();

        if ($imageOrEntity instanceof Product && $imageOrEntity->isVariant()) {
            $variantImage = $this->imageFacade->findImageByEntity(
                $this->imageConfig->getEntityName($imageOrEntity),
                $imageOrEntity->getId(),
                $attributes['type']
            );

            if ($variantImage !== null) {
                return parent::getImageHtml($variantImage, $attributes);
            } else {
                $mainVariant = $imageOrEntity->getMainVariant();

                return parent::getImageHtml($mainVariant, $attributes);
            }
        }

        return parent::getImageHtml($imageOrEntity, $attributes);
    }
}
