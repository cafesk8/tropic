<?php

declare(strict_types=1);

namespace App\Model\Product\View;

use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\ReadModelBundle\Image\ImageViewFacade;

class MainVariantGroupProductViewFactory
{
    /**
     * @var \Shopsys\ReadModelBundle\Image\ImageViewFacade
     */
    private $imageViewFacade;

    /**
     * @param \Shopsys\ReadModelBundle\Image\ImageViewFacade $imageViewFacade
     */
    public function __construct(ImageViewFacade $imageViewFacade)
    {
        $this->imageViewFacade = $imageViewFacade;
    }

    /**
     * @param array $productArray
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroupOfCurrentCustomer
     * @return \App\Model\Product\View\MainVariantGroupProductView[]
     */
    public function createMultipleFromArray(array $productArray, PricingGroup $pricingGroupOfCurrentCustomer): array
    {
        $pricingGroupId = $pricingGroupOfCurrentCustomer->getId();
        $mainVariantGroupProductViews = [];
        $mainVariantGroupProductsData = $this->filterMainVariantGroupProductDataByPricingGroupId($productArray['main_variant_group_products'], $pricingGroupId);
        $imageViewsForMainVariantGroupProducts = $this->imageViewFacade->getForEntityIds(Product::class, array_column($mainVariantGroupProductsData, 'id'));
        foreach ($mainVariantGroupProductsData as $mainVariantGroupProductData) {
            $mainVariantGroupProductId = $mainVariantGroupProductData['id'];
            $mainVariantGroupProductViews[] = new MainVariantGroupProductView(
                $mainVariantGroupProductData['name'],
                $imageViewsForMainVariantGroupProducts[$mainVariantGroupProductId]
            );
        }

        return $mainVariantGroupProductViews;
    }

    /**
     * @param \App\Model\Product\Product[] $mainVariantGroupProducts
     * @param \Shopsys\ReadModelBundle\Image\ImageView[] $imageViewsForMainVariantGroupProducts
     * @return \App\Model\Product\View\MainVariantGroupProductView[]
     */
    public function createMultipleFromMainVariantGroupProducts(array $mainVariantGroupProducts, array $imageViewsForMainVariantGroupProducts): array
    {
        $mainVariantGroupProductViews = [];
        foreach ($mainVariantGroupProducts as $mainVariantGroupProduct) {
            $mainVariantGroupProductId = $mainVariantGroupProduct->getId();
            $mainVariantGroupProductViews[] = new MainVariantGroupProductView(
                $mainVariantGroupProduct->getName(),
                $imageViewsForMainVariantGroupProducts[$mainVariantGroupProductId]
            );
        }

        return $mainVariantGroupProductViews;
    }

    /**
     * @param array $mainVariantGroupProductDataForAllPricingGroups
     * @param int $pricingGroupId
     * @return array
     */
    private function filterMainVariantGroupProductDataByPricingGroupId(array $mainVariantGroupProductDataForAllPricingGroups, int $pricingGroupId): array
    {
        $filteredData = [];
        foreach ($mainVariantGroupProductDataForAllPricingGroups as $mainVariantGroupProductData) {
            if ($mainVariantGroupProductData['pricing_group_id'] === $pricingGroupId) {
                $filteredData[] = $mainVariantGroupProductData;
            }
        }

        return $filteredData;
    }
}
