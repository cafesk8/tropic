<?php

declare(strict_types=1);

namespace App\Model\Product\View;

use App\Model\Product\BestsellingProduct\CachedBestsellingProductFacade;
use App\Model\Product\LastVisitedProducts\LastVisitedProductsFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFacade;
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
 */
class ListedProductViewElasticFacade extends BaseListedProductViewElasticFacade
{
    /**
     * @var \App\Model\Product\BestsellingProduct\CachedBestsellingProductFacade
     */
    private $cachedBestsellingProductFacade;

    /**
     * @var \App\Model\Product\LastVisitedProducts\LastVisitedProductsFacade
     */
    private $lastVisitedProductsFacade;

    /**
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFacade $productAccessoryFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \Shopsys\FrameworkBundle\Model\Product\TopProduct\TopProductFacade $topProductFacade
     * @param \App\Model\Product\ProductOnCurrentDomainElasticFacade $productOnCurrentDomainFacade
     * @param \App\Model\Product\View\ListedProductViewFactory $listedProductViewFactory
     * @param \Shopsys\ReadModelBundle\Product\Action\ProductActionViewFacade $productActionViewFacade
     * @param \Shopsys\ReadModelBundle\Image\ImageViewFacade $imageViewFacade
     * @param \App\Model\Product\BestsellingProduct\CachedBestsellingProductFacade $cachedBestsellingProductFacade
     * @param \App\Model\Product\LastVisitedProducts\LastVisitedProductsFacade $lastVisitedProductsFacade
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
        LastVisitedProductsFacade $lastVisitedProductsFacade
    ) {
        parent::__construct($productFacade, $productAccessoryFacade, $domain, $currentCustomerUser, $topProductFacade, $productOnCurrentDomainFacade, $listedProductViewFactory, $productActionViewFacade, $imageViewFacade);
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

        return $this->createFromArray($this->productOnCurrentDomainFacade->getHitsForIds($bestsellingProductIds));
    }
}
