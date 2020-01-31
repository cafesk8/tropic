<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use Picqer\Barcode\BarcodeGeneratorPNG;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Component\Image\ImageLocator;
use Shopsys\FrameworkBundle\Model\Customer\User;
use Shopsys\ReadModelBundle\Twig\ImageExtension as BaseImageExtension;
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
     * @param \Shopsys\ShopBundle\Component\Image\ImageFacade $imageFacade
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
            new TwigFunction('barcodeImageByUser', [$this, 'getBarcodeImageByUser']),
        ]);
    }

    private function getImagePlaceholder()
    {
        return $this->assetsPackage->getUrl('assets/frontend/images/design/placeholder.gif');
    }

    /**
     * Copy-pasted from @see \Shopsys\FrameworkBundle\Twig\ImageExtension
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

    /**
     * @param \Shopsys\ShopBundle\Model\Customer\User $user
     * @return string
     */
    public function getBarcodeImageByUser(User $user): string
    {
        $barcodeGenerator = new BarcodeGeneratorPNG();

        return 'data:image/png;base64,' . base64_encode($barcodeGenerator->getBarcode($user->getEan(), $barcodeGenerator::TYPE_EAN_13));
    }
}
