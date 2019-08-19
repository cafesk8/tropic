<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Component\Plugin\PluginCrudExtensionFacade;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\FrameworkBundle\Model\Category\CategoryRepository;
use Shopsys\FrameworkBundle\Model\Customer\CurrentCustomer;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupRepository;
use Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryRepository;
use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade;
use Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculationScheduler;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculation;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler;
use Shopsys\FrameworkBundle\Model\Product\Product;
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
use Shopsys\ShopBundle\Component\GoogleApi\GoogleClient;
use Shopsys\ShopBundle\Component\GoogleApi\Youtube\YoutubeView;
use Shopsys\ShopBundle\Model\Category\Category;
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
     * @var \Shopsys\ShopBundle\Model\Product\ProductDataFactory
     */
    private $productDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Category\CategoryRepository
     */
    private $categoryRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\CachedProductDistinguishingParameterValueFacade
     */
    private $cachedProductDistinguishingParameterValueFacade;

    /**
     * @var \Shopsys\ShopBundle\Component\GoogleApi\GoogleClient
     */
    private $googleClient;

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
     * @param \Shopsys\ShopBundle\Model\Product\ProductDataFactory $productDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Category\CategoryRepository $categoryRepository
     * @param \Shopsys\ShopBundle\Model\Product\CachedProductDistinguishingParameterValueFacade $cachedProductDistinguishingParameterValueFacade
     * @param \Shopsys\ShopBundle\Component\GoogleApi\GoogleClient $googleClient
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
        StoreFacade $storeFacade,
        ProductDataFactory $productDataFactory,
        CategoryRepository $categoryRepository,
        CachedProductDistinguishingParameterValueFacade $cachedProductDistinguishingParameterValueFacade,
        GoogleClient $googleClient
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
        $this->productDataFactory = $productDataFactory;
        $this->categoryRepository = $categoryRepository;
        $this->cachedProductDistinguishingParameterValueFacade = $cachedProductDistinguishingParameterValueFacade;
        $this->googleClient = $googleClient;
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

        $this->cachedProductDistinguishingParameterValueFacade->invalidCacheByProduct($product);

        return $product;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter $parameter
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getProductsWithDistinguishingParameter(Parameter $parameter): array
    {
        return $this->productRepository->getProductsWithDistinguishingParameter($parameter);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     */
    public function flushProduct(Product $product): void
    {
        $this->em->flush($product);
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
     * @param string $transferNumber
     * @return \Shopsys\ShopBundle\Model\Product\Product|null
     */
    public function findByTransferNumber(string $transferNumber): ?Product
    {
        return $this->productRepository->findByTransferNumber($transferNumber);
    }

    /**
     * @param string $ean
     * @return \Shopsys\FrameworkBundle\Model\Product\Product|null
     */
    public function findByEan(string $ean): ?Product
    {
        return $this->productRepository->findByEan($ean);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     */
    public function appendParentCategoriesByProduct(Product $product): void
    {
        $productData = $this->productDataFactory->createFromProduct($product);

        foreach ($this->domain->getAll() as $domainConfig) {
            if (array_key_exists($domainConfig->getId(), $productData->categoriesByDomainId) === false) {
                return;
            }
            $this->appendParentCategories($productData, $domainConfig->getId());
        }
        $this->edit($product->getId(), $productData);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductData $productData
     * @param int $domainId
     */
    private function appendParentCategories(ProductData $productData, int $domainId): void
    {
        $newCategories = [];
        foreach ($productData->categoriesByDomainId[$domainId] as $category) {
            $path = $this->categoryRepository->getPath($category);
            foreach ($path as $parentCategory) {
                if ($parentCategory->getParent() !== null) {
                    $newCategories[$parentCategory->getId()] = $parentCategory;
                }
            }
            $productData->categoriesByDomainId[$domainId] = $newCategories;
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
     */
    public function removeProductCategoryDomainByProductAndCategory(Product $product, Category $category): void
    {
        $productData = $this->productDataFactory->createFromProduct($product);
        $isSomeCategoryRemoveFromProduct = false;
        foreach ($this->domain->getAllIds() as $domainId) {
            $key = false;
            if (array_key_exists($domainId, $productData->categoriesByDomainId)) {
                $key = array_search($category, $productData->categoriesByDomainId[$domainId], true);
            }
            if ($key !== false) {
                unset($productData->categoriesByDomainId[$domainId][$key]);
                $isSomeCategoryRemoveFromProduct = true;
            }
        }

        if ($isSomeCategoryRemoveFromProduct === true) {
            $product->edit($this->productCategoryDomainFactory, $productData, $this->productPriceRecalculationScheduler);
        }
    }

    /**
     * @param int $limit
     * @param int $page
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getWithEan(int $limit, int $page): array
    {
        return $this->productRepository->getWithEan($limit, $page);
    }

    /**
     * @param string $ean
     * @return \Shopsys\ShopBundle\Model\Product\Product|null
     */
    public function findOneByEan(string $ean): ?Product
    {
        return $this->productRepository->findOneByEan($ean);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Money\Money[]|null[] $manualInputPrices
     */
    protected function refreshProductManualInputPrices(Product $product, array $manualInputPrices)
    {
        foreach ($this->pricingGroupRepository->getAll() as $pricingGroup) {
            if (isset($manualInputPrices[$pricingGroup->getId()]) === true) {
                $this->productManualInputPriceFacade->refresh($product, $pricingGroup, $manualInputPrices[$pricingGroup->getId()]);
            }
        }
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\ShopBundle\Component\GoogleApi\Youtube\YoutubeView|null
     */
    public function getYoutubeView(Product $product): ?YoutubeView
    {
        $youtubeDetail = null;
        if ($product->getYoutubeVideoId() !== null) {
            $youtubeResponse = $this->googleClient->getVideoList($product->getYoutubeVideoId());
            if ($youtubeResponse->getPageInfo()->getTotalResults() > 0) {
                /** @var \Google_Service_YouTube_Video $youtubeVideoItem */
                $youtubeVideoItem = $youtubeResponse->getItems()[0];
                $youtubeDetail = new YoutubeView(
                    $product->getYoutubeVideoId(),
                    $youtubeVideoItem->getSnippet()->getThumbnails()->getDefault()->url,
                    $youtubeVideoItem->getSnippet()->getTitle()
                );
            }
        }

        return $youtubeDetail;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $products
     * @return \Shopsys\ShopBundle\Component\GoogleApi\Youtube\YoutubeView[]
     */
    public function getYoutubeViewForMainVariants(array $products): array
    {
        $youtubeViewForMainVariants = [];

        foreach ($products as $product) {
            $youtubeViewForMainVariants[$product->getId()] = $this->getYoutubeView($product);
        }

        return $youtubeViewForMainVariants;
    }
}
