<?php

declare(strict_types=1);

namespace App\Model\Product\View;

use App\Model\Pricing\Group\PricingGroup;
use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Product\BestsellingProduct\CachedBestsellingProductFacade;
use App\Model\Product\Group\ProductGroup;
use App\Model\Product\Group\ProductGroupFacade;
use App\Model\Product\LastVisitedProducts\LastVisitedProductsFacade;
use App\Model\Product\PriceBombProduct\PriceBombProductFacade;
use App\Model\Product\Product;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFacade;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\FrameworkBundle\Model\Product\ProductFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainFacadeInterface;
use Shopsys\FrameworkBundle\Model\Product\TopProduct\TopProductFacade;
use Shopsys\ReadModelBundle\Image\ImageViewFacade;
use Shopsys\ReadModelBundle\Product\Action\ProductActionViewFacade;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductViewElasticFacade as BaseListedProductViewElasticFacade;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFactory;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @property \App\Model\Product\View\ListedProductViewFactory $listedProductViewFactory
 * @property \App\Model\Product\ProductOnCurrentDomainElasticFacade $productOnCurrentDomainFacade
 * @property \App\Model\Product\ProductFacade $productFacade
 * @method \App\Model\Product\View\ListedProductView[] createFromProducts(array $products)
 */
class ListedProductViewElasticFacade extends BaseListedProductViewElasticFacade
{
    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \App\Model\Product\BestsellingProduct\CachedBestsellingProductFacade
     */
    private $cachedBestsellingProductFacade;

    /**
     * @var \App\Model\Product\LastVisitedProducts\LastVisitedProductsFacade
     */
    private $lastVisitedProductsFacade;

    /**
     * @var \App\Model\Product\PriceBombProduct\PriceBombProductFacade
     */
    protected $priceBombProductFacade;

    /**
     * @var \App\Model\Product\Group\ProductGroupFacade
     */
    private $productGroupFacade;

    /**
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFacade $productAccessoryFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \Shopsys\FrameworkBundle\Model\Product\TopProduct\TopProductFacade $topProductFacade
     * @param \App\Model\Product\ProductOnCurrentDomainElasticFacade $productOnCurrentDomainFacade
     * @param \Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFactory $listedProductViewFactory
     * @param \Shopsys\ReadModelBundle\Product\Action\ProductActionViewFacade $productActionViewFacade
     * @param \Shopsys\ReadModelBundle\Image\ImageViewFacade $imageViewFacade
     * @param \App\Model\Product\BestsellingProduct\CachedBestsellingProductFacade $cachedBestsellingProductFacade
     * @param \App\Model\Product\LastVisitedProducts\LastVisitedProductsFacade $lastVisitedProductsFacade
     * @param \App\Model\Product\PriceBombProduct\PriceBombProductFacade $priceBombProductFacade
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Product\Group\ProductGroupFacade $productGroupFacade
     */
    public function __construct(
        ProductFacade $productFacade,
        ProductAccessoryFacade $productAccessoryFacade,
        Domain $domain,
        CurrentCustomerUser $currentCustomerUser,
        TopProductFacade $topProductFacade,
        ProductOnCurrentDomainFacadeInterface $productOnCurrentDomainFacade,
        ListedProductViewFactory $listedProductViewFactory,
        ProductActionViewFacade $productActionViewFacade,
        ImageViewFacade $imageViewFacade,
        CachedBestsellingProductFacade $cachedBestsellingProductFacade,
        LastVisitedProductsFacade $lastVisitedProductsFacade,
        PriceBombProductFacade $priceBombProductFacade,
        PricingGroupFacade $pricingGroupFacade,
        ProductGroupFacade $productGroupFacade
    ) {
        parent::__construct($productFacade, $productAccessoryFacade, $domain, $currentCustomerUser, $topProductFacade, $productOnCurrentDomainFacade, $listedProductViewFactory, $productActionViewFacade, $imageViewFacade);
        $this->cachedBestsellingProductFacade = $cachedBestsellingProductFacade;
        $this->lastVisitedProductsFacade = $lastVisitedProductsFacade;
        $this->priceBombProductFacade = $priceBombProductFacade;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->productGroupFacade = $productGroupFacade;
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

        return $this->createFromArray($this->productOnCurrentDomainFacade->getSellableHitsForIds($lastVisitedProductIds));
    }

    /**
     * @param \App\Model\Category\Category $category
     * @return \Shopsys\ReadModelBundle\Product\Listed\ListedProductView[]
     */
    public function getAllOfferedBestsellingProducts(Category $category): array
    {
        $bestsellingProductIds = $this->cachedBestsellingProductFacade->getAllOfferedBestsellingProductIds(
            $this->domain->getId(),
            $category,
            $this->currentCustomerUser->getPricingGroup()
        );

        return $this->createFromArray($this->productOnCurrentDomainFacade->getSellableHitsForIds($bestsellingProductIds));
    }

    /**
     * @param int|null $limit
     * @return \Shopsys\ReadModelBundle\Product\Listed\ListedProductView[]
     */
    public function getPriceBombProducts(?int $limit = null): array
    {
        $priceBombProducts = $this->priceBombProductFacade->getPriceBombProducts(
            $this->domain->getId(),
            $this->currentCustomerUser->getPricingGroup(),
            $limit
        );

        return $this->createFromProducts($priceBombProducts);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\View\ListedProductView[]
     */
    public function getParentSetsByProduct(Product $product, int $domainId, PricingGroup $pricingGroup): array
    {
        return $this->createFromProducts(array_map(function (ProductGroup $productGroup) {
            return $productGroup->getMainProduct();
        }, $this->productGroupFacade->getVisibleByItem($product, $domainId, $pricingGroup)));
    }

    /**
     * @param array $productsArray
     * @return \App\Model\Product\View\ListedProductView[]
     */
    protected function createFromArray(array $productsArray): array
    {
        $productIds = [];

        foreach ($productsArray as $productArray) {
            $productIds[] = $productArray['id'];

            foreach ($productArray['group_items'] as $groupItem) {
                $productIds[] = $groupItem['id'];
            }
        }

        $imageViews = $this->imageViewFacade->getForEntityIds(BaseProduct::class, $productIds);
        $salePricingGroup = $this->pricingGroupFacade->getSalePricePricingGroup($this->domain->getId());

        $listedProductViews = [];
        foreach ($productsArray as $productArray) {
            $productId = $productArray['id'];

            foreach ($productArray['group_items'] as &$groupItem) {
                $groupItem['image'] = $imageViews[$groupItem['id']];
            }

            $listedProductViews[$productId] = $this->listedProductViewFactory->createFromArray(
                $productArray,
                $imageViews[$productId],
                $this->productActionViewFacade->getForArray($productArray),
                $productArray['is_in_any_sale_stock'] === true ? $salePricingGroup : $this->currentCustomerUser->getPricingGroup()
            );
        }

        return $listedProductViews;
    }
}
