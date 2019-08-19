<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Controller\Front;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Category\Category;
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
use Shopsys\FrameworkBundle\Model\Product\BestsellingProduct\CachedBestsellingProductFacade;
use Shopsys\ShopBundle\Model\Product\ProductOnCurrentDomainElasticFacade;

class BestsellingProductController extends FrontBaseController
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\BestsellingProduct\CachedBestsellingProductFacade
     */
    private $cachedBestsellingProductFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer
     */
    private $currentCustomer;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductOnCurrentDomainElasticFacade
     */
    private $productOnCurrentDomainFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\BestsellingProduct\CachedBestsellingProductFacade $cachedBestsellingProductFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     * @param \Shopsys\ShopBundle\Model\Product\ProductOnCurrentDomainElasticFacade $productOnCurrentDomainFacade
     */
    public function __construct(
        CachedBestsellingProductFacade $cachedBestsellingProductFacade,
        Domain $domain,
        CurrentCustomer $currentCustomer,
        ProductOnCurrentDomainElasticFacade $productOnCurrentDomainFacade
    ) {
        $this->cachedBestsellingProductFacade = $cachedBestsellingProductFacade;
        $this->domain = $domain;
        $this->currentCustomer = $currentCustomer;
        $this->productOnCurrentDomainFacade = $productOnCurrentDomainFacade;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Category\Category $category
     */
    public function listAction(Category $category)
    {
        $bestsellingProducts = $this->cachedBestsellingProductFacade->getAllOfferedBestsellingProducts(
            $this->domain->getId(),
            $category,
            $this->currentCustomer->getPricingGroup()
        );

        return $this->render('@ShopsysShop/Front/Content/Product/bestsellingProductsList.html.twig', [
            'products' => $bestsellingProducts,
            'variantsIndexedByMainVariantId' => $this->productOnCurrentDomainFacade->getVariantsIndexedByMainVariantId($bestsellingProducts),
        ]);
    }
}
