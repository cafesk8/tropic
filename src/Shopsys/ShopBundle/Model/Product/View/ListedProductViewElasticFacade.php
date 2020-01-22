<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\View;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
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
use Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade;

/**
 * @property \Shopsys\ShopBundle\Model\Product\View\ListedProductViewFactory $listedProductViewFactory
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
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFacade $productAccessoryFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     * @param \Shopsys\FrameworkBundle\Model\Product\TopProduct\TopProductFacade $topProductFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductOnCurrentDomainFacadeInterface $productOnCurrentDomainFacade
     * @param \Shopsys\ShopBundle\Model\Product\View\ListedProductViewFactory $listedProductViewFactory
     * @param \Shopsys\ReadModelBundle\Product\Action\ProductActionViewFacade $productActionViewFacade
     * @param \Shopsys\ReadModelBundle\Image\ImageViewFacade $imageViewFacade
     * @param \Shopsys\ShopBundle\Model\Product\View\MainVariantGroupProductViewFactory $mainVariantGroupProductViewFactory
     * @param \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade $mainVariantGroupFacade
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
        MainVariantGroupFacade $mainVariantGroupFacade
    ) {
        parent::__construct($productFacade, $productAccessoryFacade, $domain, $currentCustomer, $topProductFacade, $productOnCurrentDomainFacade, $listedProductViewFactory, $productActionViewFacade, $imageViewFacade);
        $this->mainVariantGroupProductViewFactory = $mainVariantGroupProductViewFactory;
        $this->mainVariantGroupFacade = $mainVariantGroupFacade;
    }

    /**
     * @param array $productsArray
     * @return \Shopsys\ReadModelBundle\Product\Listed\ListedProductView[]
     */
    protected function createFromArray(array $productsArray): array
    {
        $listedProductViews = [];
        $imageViews = $this->imageViewFacade->getForEntityIds(Product::class, array_column($productsArray, 'id'));
        $pricingGroupOfCurrentCustomer = $this->currentCustomer->getPricingGroup();
        foreach ($productsArray as $productArray) {
            $productId = $productArray['id'];
            $listedProductViews[$productId] = $this->listedProductViewFactory->createFromArray(
                $productArray,
                $imageViews[$productId],
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
        $imageViews = $this->imageViewFacade->getForEntityIds(Product::class, $this->getIdsForProducts($products));
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
                $imageViews[$productId],
                $productActionViews[$productId],
                $this->mainVariantGroupProductViewFactory->createMultipleFromMainVariantGroupProducts($mainVariantGroupProducts, $imageViewsForMainVariantGroupProducts),
                $variantsIndexedByPricingGroupIdAndMainVariantId[$currentCustomerPricingGroup->getId()]
            );
        }

        return $listedProductViews;
    }
}
