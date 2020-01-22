<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\View;

use Shopsys\ReadModelBundle\Image\ImageView;

/**
 * Represents a product from main variant group products in listed product view
 * @see \Shopsys\ShopBundle\Model\Product\View\ListedProductView
 * @see \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup
 */
class MainVariantGroupProductView
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var \Shopsys\ReadModelBundle\Image\ImageView|null
     */
    private $image;

    /**
     * @param string $name
     * @param \Shopsys\ReadModelBundle\Image\ImageView|null $image
     */
    public function __construct(string $name, ?ImageView $image)
    {
        $this->name = $name;
        $this->image = $image;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return \Shopsys\ReadModelBundle\Image\ImageView|null
     */
    public function getImage(): ?ImageView
    {
        return $this->image;
    }
}
