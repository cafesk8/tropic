<?php

declare(strict_types=1);

namespace App\Model\Product\Group;

use App\Component\Transfer\Pohoda\Product\PohodaProductExportRepository;
use App\Model\Pricing\Group\PricingGroup;
use App\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\ReadModelBundle\Image\ImageViewFacade;

class ProductGroupFacade
{
    /**
     * @var \App\Model\Product\Group\ProductGroupRepository
     */
    private $productGroupRepository;

    /**
     * @var \Shopsys\ReadModelBundle\Image\ImageViewFacade
     */
    private $imageViewFacade;

    /**
     * @param \App\Model\Product\Group\ProductGroupRepository $productGroupRepository
     * @param \Shopsys\ReadModelBundle\Image\ImageViewFacade $imageViewFacade
     */
    public function __construct(ProductGroupRepository $productGroupRepository, ImageViewFacade $imageViewFacade)
    {
        $this->productGroupRepository = $productGroupRepository;
        $this->imageViewFacade = $imageViewFacade;
    }

    /**
     * @param \App\Model\Product\Product $mainProduct
     * @return \App\Model\Product\Group\ProductGroup[]
     */
    public function getAllByMainProduct(Product $mainProduct): array
    {
        return $this->productGroupRepository->getAllByMainProduct($mainProduct);
    }

    /**
     * @param \App\Model\Product\Product $item
     * @return \App\Model\Product\Group\ProductGroup[]
     */
    public function getAllByItem(Product $item): array
    {
        return $this->productGroupRepository->getAllByItem($item);
    }

    /**
     * @param \App\Model\Product\Product $item
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Group\ProductGroup[]
     */
    public function getVisibleByItem(Product $item, int $domainId, PricingGroup $pricingGroup): array
    {
        return $this->productGroupRepository->getVisibleByItem($item, $domainId, $pricingGroup);
    }

    /**
     * @param \App\Model\Product\Product $mainProduct
     * @param string $locale
     * @return array[]
     */
    public function getAllForElasticByMainProduct(Product $mainProduct, string $locale): array
    {
        $productGroups = $this->getAllByMainProduct($mainProduct);
        $imageViews = $this->imageViewFacade->getForEntityIds(BaseProduct::class, array_map(function (ProductGroup $productGroup) {
            return $productGroup->getItem()->getId();
        }, $productGroups));

        return array_map(function (ProductGroup $productGroup) use ($locale, $imageViews) {
            return [
                'id' => $productGroup->getItem()->getId(),
                'name' => $productGroup->getItem()->getName($locale),
                'amount' => $productGroup->getItemCount(),
                'image' => $imageViews[$productGroup->getItem()->getId()],
            ];
        }, $productGroups);
    }

    /**
     * @param \App\Model\Product\Group\ProductGroup $productGroup
     * @return int
     */
    public function getStockQuantity(ProductGroup $productGroup): int
    {
        $quantity = 0;

        foreach ($productGroup->getItem()->getStoreStocks() as $storeStock) {
            if (!in_array((int)$storeStock->getStore()->getExternalNumber(), [
                PohodaProductExportRepository::POHODA_STOCK_TROPIC_ID,
                PohodaProductExportRepository::POHODA_STOCK_EXTERNAL_ID,
            ], true)) {
                continue;
            }

            $quantity += $storeStock->getStockQuantity();
        }

        return (int)floor($quantity / $productGroup->getItemCount());
    }
}
