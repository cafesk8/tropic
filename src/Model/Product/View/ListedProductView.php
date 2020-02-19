<?php

declare(strict_types=1);

namespace App\Model\Product\View;

use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice;
use Shopsys\ReadModelBundle\Image\ImageView;
use Shopsys\ReadModelBundle\Product\Action\ProductActionView;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductView as BaseListedProductView;

/**
 * @property \App\Model\Product\Pricing\ProductPrice $sellingPrice
 * @method \App\Model\Product\Pricing\ProductPrice getSellingPrice()
 */
class ListedProductView extends BaseListedProductView
{
    /**
     * @var \App\Model\Product\View\MainVariantGroupProductView[]
     */
    private $mainVariantGroupProductViews;

    /**
     * @var array
     */
    private $distinguishingParameterValues;

    /**
     * @param int $id
     * @param string $name
     * @param string|null $shortDescription
     * @param string $availability
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice $sellingPrice
     * @param array $flagIds
     * @param \Shopsys\ReadModelBundle\Product\Action\ProductActionView $action
     * @param \Shopsys\ReadModelBundle\Image\ImageView|null $image
     * @param array $mainVariantGroupProductViews
     * @param string[] $distinguishingParameterValues
     */
    public function __construct(
        int $id,
        string $name,
        ?string $shortDescription,
        string $availability,
        ProductPrice $sellingPrice,
        array $flagIds,
        ProductActionView $action,
        ?ImageView $image,
        array $mainVariantGroupProductViews,
        array $distinguishingParameterValues
    ) {
        parent::__construct($id, $name, $shortDescription, $availability, $sellingPrice, $flagIds, $action, $image);

        $this->mainVariantGroupProductViews = $mainVariantGroupProductViews;
        $this->distinguishingParameterValues = $distinguishingParameterValues;
    }

    /**
     * @return \App\Model\Product\View\MainVariantGroupProductView[]
     */
    public function getMainVariantGroupProductViews(): array
    {
        return $this->mainVariantGroupProductViews;
    }

    /**
     * @return array
     */
    public function getDistinguishingParameterValues(): array
    {
        return $this->distinguishingParameterValues;
    }
}