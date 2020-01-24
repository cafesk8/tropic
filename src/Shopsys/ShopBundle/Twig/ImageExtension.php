<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Twig;

use Picqer\Barcode\BarcodeGeneratorPNG;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Component\Image\ImageLocator;
use Shopsys\FrameworkBundle\Model\Customer\User;
use Shopsys\ReadModelBundle\Image\ImageView;
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
            new TwigFunction('imagePlaceholder', [$this, 'getImagePlaceholder']),
            new TwigFunction('barcodeImageByUser', [$this, 'getBarcodeImageByUser']),
        ]);
    }

    public function getImagePlaceholder()
    {
        return $this->assetsPackage->getUrl('assets/frontend/images/design/placeholder.gif');
    }

    /**
     * @param \Shopsys\ShopBundle\Component\Image\Image|\Shopsys\ReadModelBundle\Image\ImageView|Object|null $imageOrEntity
     * @param array $attributes
     * @return string
     */
    public function getImageHtml($imageOrEntity, array $attributes = []): string
    {
        if ($imageOrEntity !== null && !($imageOrEntity instanceof ImageView)) {
            $this->preventDefault($attributes);
            try {
                $image = $this->imageFacade->getImageByObject($imageOrEntity, $attributes['type']);
            } catch (\Shopsys\FrameworkBundle\Component\Image\Exception\ImageNotFoundException $e) {
                return $this->getNoimageHtml();
            }

            $useLazyLoading = array_key_exists('lazy', $attributes) ? (bool)$attributes['lazy'] : false;
            if ($useLazyLoading === true) {
                $attributes['src'] = $this->getImagePlaceholder();
                $attributes['data-original'] = $this->getImageUrl($image, $attributes['size'], $attributes['type']);
                $attributes['class'] = array_key_exists('class', $attributes) ? $attributes['class'] . ' js-lazy-load' : 'js-lazy-load';
            }
        }

        return parent::getImageHtml($imageOrEntity, $attributes);
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
