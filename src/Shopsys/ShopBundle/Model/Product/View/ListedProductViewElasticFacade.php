<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\View;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
use Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFacade;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainFacadeInterface;
use Shopsys\FrameworkBundle\Model\Product\TopProduct\TopProductFacade;
use Shopsys\ReadModelBundle\Image\ImageViewFacade;
use Shopsys\ReadModelBundle\Product\Action\ProductActionViewFacade;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductViewElasticFacade as BaseListedProductViewElasticFacade;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFactory;
use Shopsys\ShopBundle\Model\Product\BestsellingProduct\CachedBestsellingProductFacade;
use Shopsys\ShopBundle\Model\Product\LastVisitedProducts\LastVisitedProductsFacade;
use Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @property \Shopsys\ShopBundle\Model\Product\View\ListedProductViewFactory $listedProductViewFactory
 * @property \Shopsys\ShopBundle\Model\Product\ProductOnCurrentDomainElasticFacade $productOnCurrentDomainFacade
 * @property \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
 */
class ListedProductViewElasticFacade extends BaseListedProductViewElasticFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\View\MainVariantGroupProductViewFactory
     */
    private $mainVariantGroupProductViewFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade
     */
    private $mainVariantGroupFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\BestsellingProduct\CachedBestsellingProductFacade
     */
    private $cachedBestsellingProductFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\LastVisitedProducts\LastVisitedProductsFacade
     */
    private $lastVisitedProductsFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFacade $productAccessoryFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     * @param \Shopsys\FrameworkBundle\Model\Product\TopProduct\TopProductFacade $topProductFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductOnCurrentDomainElasticFacade $productOnCurrentDomainFacade
     * @param \Shopsys\ShopBundle\Model\Product\View\ListedProductViewFactory $listedProductViewFactory
     * @param \Shopsys\ReadModelBundle\Product\Action\ProductActionViewFacade $productActionViewFacade
     * @param \Shopsys\ReadModelBundle\Image\ImageViewFacade $imageViewFacade
     * @param \Shopsys\ShopBundle\Model\Product\View\MainVariantGroupProductViewFactory $mainVariantGroupProductViewFactory
     * @param \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade $mainVariantGroupFacade
     * @param \Shopsys\ShopBundle\Model\Product\BestsellingProduct\CachedBestsellingProductFacade $cachedBestsellingProductFacade
     * @param \Shopsys\ShopBundle\Model\Product\LastVisitedProducts\LastVisitedProductsFacade $lastVisitedProductsFacade
     */
    public function __construct(
        ProductFacade $productFacade,
        ProductAccessoryFacade $productAccessoryFacade,
        Domain $domain,
        CurrentCustomer $currentCustomer,
        TopProductFacade $topProductFacade,
        ProductOnCurrentDomainFacadeInterface $productOnCurrentDomainFacade,
        ListedProductViewFactory $listedProductViewFactory,
        ProductActionViewFacade $productActionViewFacade,
        ImageViewFacade $imageViewFacade,
        MainVariantGroupProductViewFactory $mainVariantGroupProductViewFactory,
        MainVariantGroupFacade $mainVariantGroupFacade,
        CachedBestsellingProductFacade $cachedBestsellingProductFacade,
        LastVisitedProductsFacade $lastVisitedProductsFacade
    ) {
        parent::__construct($productFacade, $productAccessoryFacade, $domain, $currentCustomer, $topProductFacade, $productOnCurrentDomainFacade, $listedProductViewFactory, $productActionViewFacade, $imageViewFacade);
        $this->mainVariantGroupProductViewFactory = $mainVariantGroupProductViewFactory;
        $this->mainVariantGroupFacade = $mainVariantGroupFacade;
        $this->cachedBestsellingProductFacade = $cachedBestsellingProductFacade;
        $this->lastVisitedProductsFacade = $lastVisitedProductsFacade;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\ParameterBag $cookies
     * @param int $limit
     * @return \Shopsys\ReadModelBundle\Product\Listed\ListedProductView[]
     */
    public function getProductsFromCookieSortedByNewest(ParameterBag $cookies, int $limit): array
    {
        $lastVisitedProductIds = $this->lastVisitedProductsFacade->getProductIdsFromCookieSortedByNewest(
            $cookies,
            $limit
        );

        return $this->createFromArray($this->productOnCurrentDomainFacade->getHitsForIds($lastVisitedProductIds));
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
     * @return \Shopsys\ReadModelBundle\Product\Listed\ListedProductView[]
     */
    public function getAllOfferedBestsellingProducts(Category $category): array
    {
        $bestsellingProductIds = $this->cachedBestsellingProductFacade->getAllOfferedBestsellingProductIds(
            $this->domain->getId(),
            $category,
            $this->currentCustomer->getPricingGroup()
        );

        return $this->createFromArray($this->productOnCurrentDomainFacade->getHitsForIds($bestsellingProductIds));
    }

    /**
     * @param array $productsArray
     * @return \Shopsys\ShopBundle\Model\Product\View\ListedProductView[]
     */
    protected function createFromArray(array $productsArray): array
    {
        $listedProductViews = [];
        $imageViews = $this->imageViewFacade->getForEntityIds(
            Product::class,
            $this->getProductIdsForGeneratingImagesFromProductIds($productsArray)
        );
        $pricingGroupOfCurrentCustomer = $this->currentCustomer->getPricingGroup();
        foreach ($productsArray as $productArray) {
            $productId = $productArray['id'];
            $listedProductViews[$productId] = $this->listedProductViewFactory->createFromArray(
                $productArray,
                $imageViews[$productArray['main_variant_id'] ?? $productId],
                $this->productActionViewFacade->getForArray($productArray),
                $pricingGroupOfCurrentCustomer,
                $this->mainVariantGroupProductViewFactory->createMultipleFromArray($productArray, $pricingGroupOfCurrentCustomer)
            );
        }

        return $listedProductViews;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $products
     * @return \Shopsys\ShopBundle\Model\Product\View\ListedProductView[]
     */
    protected function createFromProducts(array $products): array
    {
        $imageViews = $this->imageViewFacade->getForEntityIds(
            Product::class,
            $this->getProductIdsForGeneratingImagesFromProducts($products)
        );
        $productActionViews = $this->productActionViewFacade->getForProducts($products);

        $currentCustomerPricingGroup = $this->currentCustomer->getPricingGroup();
        $productsIndexedByMainVariantGroup = $this->mainVariantGroupFacade->getProductsIndexedByMainVariantGroup($products, $currentCustomerPricingGroup);
        $variantsIndexedByPricingGroupIdAndMainVariantId = $this->productFacade->getVariantsIndexedByPricingGroupIdAndMainVariantId($products, $this->domain->getId());
        $listedProductViews = [];
        foreach ($products as $product) {
            /** @var \Shopsys\ShopBundle\Model\Product\Product $product */
            $productId = $product->getId();
            $mainVariantGroup = $product->getMainVariantGroup();
            $mainVariantGroupProducts = $mainVariantGroup !== null ? $productsIndexedByMainVariantGroup[$mainVariantGroup->getId()] : [];
            $imageViewsForMainVariantGroupProducts = $this->imageViewFacade->getForEntityIds(
                Product::class,
                $this->getIdsForProducts($mainVariantGroupProducts)
            );

            $listedProductViews[$productId] = $this->listedProductViewFactory->createFromProduct(
                $product,
                $imageViews[$product->getProductForCreatingImageAccordingToVariant()->getId()],
                $productActionViews[$productId],
                $this->mainVariantGroupProductViewFactory->createMultipleFromMainVariantGroupProducts($mainVariantGroupProducts, $imageViewsForMainVariantGroupProducts),
                $variantsIndexedByPricingGroupIdAndMainVariantId[$currentCustomerPricingGroup->getId()]
            );
        }

        return $listedProductViews;
    }

    /**
     * @param array $productsArray
     * @return int[]
     */
    private function getProductIdsForGeneratingImagesFromProductIds(array $productsArray): array
    {
        $productIdsForGeneratingImages = [];
        foreach ($productsArray as $productArray) {
            if ($productArray['main_variant_id'] !== null) {
                $productIdsForGeneratingImages[] = $productArray['main_variant_id'];
            } else {
                $productIdsForGeneratingImages[] = $productArray['id'];
            }
        }

        return $productIdsForGeneratingImages;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $products
     * @return int[]
     */
    private function getProductIdsForGeneratingImagesFromProducts(array $products): array
    {
        $productIdsForGeneratingImages = [];
        foreach ($products as $product) {
            $productIdsForGeneratingImages[] = $product->getProductForCreatingImageAccordingToVariant()->getId();
        }

        return $productIdsForGeneratingImages;
    }
}
