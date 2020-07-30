<?php

declare(strict_types=1);

namespace App\Model\Product\Set;

use App\Component\Transfer\Pohoda\Product\PohodaProductExportRepository;
use App\Model\Pricing\Group\PricingGroup;
use App\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\ReadModelBundle\Image\ImageViewFacade;

class ProductSetFacade
{
    private ProductSetRepository $productSetRepository;

    /**
     * @var \Shopsys\ReadModelBundle\Image\ImageViewFacade
     */
    private $imageViewFacade;

    /**
     * @param \App\Model\Product\Set\ProductSetRepository $productSetRepository
     * @param \Shopsys\ReadModelBundle\Image\ImageViewFacade $imageViewFacade
     */
    public function __construct(ProductSetRepository $productSetRepository, ImageViewFacade $imageViewFacade)
    {
        $this->productSetRepository = $productSetRepository;
        $this->imageViewFacade = $imageViewFacade;
    }

    /**
     * @param \App\Model\Product\Product $mainProduct
     * @return \App\Model\Product\Set\ProductSet[]
     */
    public function getAllByMainProduct(Product $mainProduct): array
    {
        return $this->productSetRepository->getAllByMainProduct($mainProduct);
    }

    /**
     * @param \App\Model\Product\Product $item
     * @return \App\Model\Product\Set\ProductSet[]
     */
    public function getAllByItem(Product $item): array
    {
        return $this->productSetRepository->getAllByItem($item);
    }

    /**
     * @param \App\Model\Product\Product $item
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Set\ProductSet[]
     */
    public function getVisibleByItem(Product $item, int $domainId, PricingGroup $pricingGroup): array
    {
        return $this->productSetRepository->getVisibleByItem($item, $domainId, $pricingGroup);
    }

    /**
     * @param \App\Model\Product\Product $mainProduct
     * @param string $locale
     * @return array[]
     */
    public function getAllForElasticByMainProduct(Product $mainProduct, string $locale): array
    {
        $productSets = $this->getAllByMainProduct($mainProduct);
        $imageViews = $this->imageViewFacade->getForEntityIds(BaseProduct::class, array_map(function (ProductSet $productSet) {
            return $productSet->getItem()->getId();
        }, $productSets));

        return array_map(function (ProductSet $productSet) use ($locale, $imageViews) {
            return [
                'id' => $productSet->getItem()->getId(),
                'name' => $productSet->getItem()->getName($locale),
                'amount' => $productSet->getItemCount(),
                'image' => $imageViews[$productSet->getItem()->getId()],
            ];
        }, $productSets);
    }

    /**
     * @param \App\Model\Product\Set\ProductSet $productSet
     * @return int
     */
    public function getStockQuantity(ProductSet $productSet): int
    {
        $quantity = 0;

        foreach ($productSet->getItem()->getStoreStocks() as $storeStock) {
            if (!in_array((int)$storeStock->getStore()->getExternalNumber(), [
                PohodaProductExportRepository::POHODA_STOCK_TROPIC_ID,
                PohodaProductExportRepository::POHODA_STOCK_EXTERNAL_ID,
            ], true)) {
                continue;
            }

            $quantity += $storeStock->getStockQuantity();
        }

        return (int)floor($quantity / $productSet->getItemCount());
    }
}
