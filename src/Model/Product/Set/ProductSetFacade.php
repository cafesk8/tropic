<?php

declare(strict_types=1);

namespace App\Model\Product\Set;

use App\Component\Image\ImageFacade;
use App\Model\Pricing\Group\PricingGroup;
use App\Model\Product\Product;
use App\Model\Store\Store;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;

class ProductSetFacade
{
    private ProductSetRepository $productSetRepository;

    private ImageFacade $imageFacade;

    /**
     * @param \App\Model\Product\Set\ProductSetRepository $productSetRepository
     * @param \App\Component\Image\ImageFacade $imageFacade
     */
    public function __construct(ProductSetRepository $productSetRepository, ImageFacade $imageFacade)
    {
        $this->productSetRepository = $productSetRepository;
        $this->imageFacade = $imageFacade;
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
    public function getAllItemsDataByMainProduct(Product $mainProduct, string $locale): array
    {
        if ($mainProduct->isSupplierSet() === true) {
            return $this->getSupplierSetItemsData($mainProduct);
        }
        $productSets = $this->getAllByMainProduct($mainProduct);
        $images = $this->imageFacade->getImagesByEntitiesIndexedByEntityId(array_map(function (ProductSet $productSet) {
            return $productSet->getItem()->getId();
        }, $productSets), BaseProduct::class);

        return array_map(function (ProductSet $productSet) use ($locale, $images) {
            $result = [
                'id' => $productSet->getItem()->getId(),
                'name' => $productSet->getItem()->getName($locale),
                'amount' => $productSet->getItemCount(),
                'image' => null,
            ];
            $image = $images[$productSet->getItem()->getId()] ?? null;
            if ($image !== null) {
                $result['image'] = [
                    'id' => $image->getId(),
                    'extension' => $image->getExtension(),
                    'entity_name' => $image->getEntityName(),
                    'type' => $image->getType(),
                ];
            }
            return $result;
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
            if (!in_array($storeStock->getStore()->getPohodaName(), [
                Store::POHODA_STOCK_TROPIC_NAME,
                Store::POHODA_STOCK_EXTERNAL_NAME,
            ], true)) {
                continue;
            }

            $quantity += $storeStock->getStockQuantity();
        }

        return (int)floor($quantity / $productSet->getItemCount());
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return array
     */
    private function getSupplierSetItemsData(Product $product): array
    {
        $productId = $product->getId();
        $supplierSetItemsData = [];
        $images = $this->imageFacade->getImagesExcludingMain($product);
        foreach ($images as $image) {
            $imageDescription = $image->getDescription();
            $supplierSetItemsData[] = [
                'id' => $productId,
                'name' => $this->imageFacade->getSupplierSetItemName($imageDescription),
                'amount' => $this->imageFacade->getSupplierSetItemCount($imageDescription),
                'image' => [
                    'id' => $image->getId(),
                    'extension' => $image->getExtension(),
                    'entity_name' => $image->getEntityName(),
                    'type' => $image->getType(),
                ],
            ];
        }

        return $supplierSetItemsData;
    }
}
