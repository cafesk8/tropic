<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Component\Plugin\PluginCrudExtensionFacade;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupRepository;
use Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryRepository;
use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade;
use Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculationScheduler;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculation;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\ProductData;
use Shopsys\FrameworkBundle\Model\Product\ProductFacade as BaseProductFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\ProductHiddenRecalculator;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository;
use Shopsys\FrameworkBundle\Model\Product\ProductSellingDeniedRecalculator;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFactoryInterface;
use Shopsys\ShopBundle\Model\Product\StoreStock\ProductStoreStockFactory;
use Shopsys\ShopBundle\Model\Store\StoreFacade;

class ProductFacade extends BaseProductFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer
     */
    private $currentCustomer;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\StoreStock\ProductStoreStockFactory
     */
    private $productStoreStockFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Store\StoreFacade
     */
    private $storeFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductRepository $productRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade $productVisibilityFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Component\Image\ImageFacade $imageFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler $productPriceRecalculationScheduler
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupRepository $pricingGroupRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceFacade $productManualInputPriceFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculationScheduler $productAvailabilityRecalculationScheduler
     * @param \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductHiddenRecalculator $productHiddenRecalculator
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductSellingDeniedRecalculator $productSellingDeniedRecalculator
     * @param \Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryRepository $productAccessoryRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade $availabilityFacade
     * @param \Shopsys\FrameworkBundle\Component\Plugin\PluginCrudExtensionFacade $pluginCrudExtensionFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductFactoryInterface $productFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFactoryInterface $productAccessoryFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactoryInterface $productCategoryDomainFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueFactoryInterface $productParameterValueFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFactoryInterface $productVisibilityFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculation $productPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer $currentCustomer
     * @param \Shopsys\ShopBundle\Model\Product\StoreStock\ProductStoreStockFactory $productStoreStockFactory
     * @param \Shopsys\ShopBundle\Model\Store\StoreFacade $storeFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        ProductRepository $productRepository,
        ProductVisibilityFacade $productVisibilityFacade,
        ParameterRepository $parameterRepository,
        Domain $domain,
        ImageFacade $imageFacade,
        ProductPriceRecalculationScheduler $productPriceRecalculationScheduler,
        PricingGroupRepository $pricingGroupRepository,
        ProductManualInputPriceFacade $productManualInputPriceFacade,
        ProductAvailabilityRecalculationScheduler $productAvailabilityRecalculationScheduler,
        FriendlyUrlFacade $friendlyUrlFacade,
        ProductHiddenRecalculator $productHiddenRecalculator,
        ProductSellingDeniedRecalculator $productSellingDeniedRecalculator,
        ProductAccessoryRepository $productAccessoryRepository,
        AvailabilityFacade $availabilityFacade,
        PluginCrudExtensionFacade $pluginCrudExtensionFacade,
        ProductFactoryInterface $productFactory,
        ProductAccessoryFactoryInterface $productAccessoryFactory,
        ProductCategoryDomainFactoryInterface $productCategoryDomainFactory,
        ProductParameterValueFactoryInterface $productParameterValueFactory,
        ProductVisibilityFactoryInterface $productVisibilityFactory,
        ProductPriceCalculation $productPriceCalculation,
        CurrentCustomer $currentCustomer,
        ProductStoreStockFactory $productStoreStockFactory,
        StoreFacade $storeFacade
    ) {
        parent::__construct(
            $em,
            $productRepository,
            $productVisibilityFacade,
            $parameterRepository,
            $domain,
            $imageFacade,
            $productPriceRecalculationScheduler,
            $pricingGroupRepository,
            $productManualInputPriceFacade,
            $productAvailabilityRecalculationScheduler,
            $friendlyUrlFacade,
            $productHiddenRecalculator,
            $productSellingDeniedRecalculator,
            $productAccessoryRepository,
            $availabilityFacade,
            $pluginCrudExtensionFacade,
            $productFactory,
            $productAccessoryFactory,
            $productCategoryDomainFactory,
            $productParameterValueFactory,
            $productVisibilityFactory,
            $productPriceCalculation
        );

        $this->currentCustomer = $currentCustomer;
        $this->productStoreStockFactory = $productStoreStockFactory;
        $this->storeFacade = $storeFacade;
    }

    /**
     * @param array $productIds
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getAllVisibleByIds(array $productIds): array
    {
        return $this->productRepository->getAllVisibleByIds(
            $this->domain->getId(),
            $this->currentCustomer->getPricingGroup(),
            $productIds
        );
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     */
    public function setAdditionalDataAfterCreate(BaseProduct $product, ProductData $productData): void
    {
        parent::setAdditionalDataAfterCreate($product, $productData);

        $this->updateProductStoreStocks($productData, $product);
    }

    /**
     * @param int $productId
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     * @return \Shopsys\ShopBundle\Model\Product\Product
     */
    public function edit($productId, ProductData $productData): Product
    {
        /** @var \Shopsys\ShopBundle\Model\Product\Product $product */
        $product = parent::edit($productId, $productData);

        $this->updateProductStoreStocks($productData, $product);
        $this->updateMainVariantGroup($productData, $product);

        return $product;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     */
    private function updateProductStoreStocks(ProductData $productData, Product $product): void
    {
        $product->clearStoreStocks();
        $this->em->flush();

        foreach ($productData->stockQuantityByStoreId as $storeId => $stockQuantity) {
            $storeStock = $this->productStoreStockFactory->create(
                $product,
                $this->storeFacade->getById($storeId),
                $stockQuantity
            );

            $product->addStoreStock($storeStock);
        }

        $this->em->flush();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     */
    private function updateMainVariantGroup(ProductData $productData, Product $product): void
    {
        $mainVariantGroup = $product->getMainVariantGroup();

        if ($mainVariantGroup === null) {
            return;
        }

        $mainVariantGroup->setDistinguishingParameter($productData->distinguishingParameterForMainVariantGroup);
        $mainVariantGroup->addProducts($productData->productsInGroup);
        $this->em->flush();
    }

    /**
     * @param int $transferNumber
     * @return \Shopsys\ShopBundle\Model\Product\Product|null
     */
    public function findByTransferNumber(int $transferNumber): ?Product
    {
        return $this->productRepository->findByTransferNumber($transferNumber);
    }
}
