<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Component\Domain\DomainHelper;
use App\Component\GoogleApi\GoogleClient;
use App\Component\GoogleApi\Youtube\YoutubeView;
use App\Component\Setting\Setting;
use App\Component\Transfer\Pohoda\Product\PohodaProductExportRepository;
use App\Model\Category\Category;
use App\Model\Category\CategoryFacade;
use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Product\Flag\FlagFacade;
use App\Model\Product\Flag\ProductFlagData;
use App\Model\Product\Flag\ProductFlagDataFactory;
use App\Model\Product\Flag\ProductFlagFacade;
use App\Model\Product\Group\ProductGroupFacade;
use App\Model\Product\Group\ProductGroupFactory;
use App\Model\Product\StoreStock\ProductStoreStockFactory;
use App\Model\Store\StoreFacade;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Google_Service_Exception;
use Psr\Log\LoggerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Domain\Exception\NoDomainSelectedException;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Component\Plugin\PluginCrudExtensionFacade;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\FrameworkBundle\Model\Category\Exception\CategoryNotFoundException;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupRepository;
use Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryRepository;
use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade;
use Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculationScheduler;
use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportScheduler;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductManualInputPriceFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculation;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductSellingPrice;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\ProductData as BaseProductData;
use Shopsys\FrameworkBundle\Model\Product\ProductFacade as BaseProductFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\ProductHiddenRecalculator;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository;
use Shopsys\FrameworkBundle\Model\Product\ProductSellingDeniedRecalculator;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFactoryInterface;

/**
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 * @property \App\Model\Product\Parameter\ParameterRepository $parameterRepository
 * @property \App\Component\Image\ImageFacade $imageFacade
 * @property \App\Model\Pricing\Group\PricingGroupRepository $pricingGroupRepository
 * @property \App\Model\Product\Pricing\ProductManualInputPriceFacade $productManualInputPriceFacade
 * @property \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
 * @property \App\Model\Product\Pricing\ProductPriceCalculation $productPriceCalculation
 * @method \App\Model\Product\Product getById(int $productId)
 * @method \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductSellingPrice[] getAllProductSellingPricesByDomainId(\App\Model\Product\Product $product, int $domainId)
 * @method createProductVisibilities(\App\Model\Product\Product $product)
 * @method refreshProductAccessories(\App\Model\Product\Product $product, \App\Model\Product\Product[] $accessories)
 * @method \App\Model\Product\Product getOneByCatnumExcludeMainVariants(string $productCatnum)
 * @method \App\Model\Product\Product getByUuid(string $uuid)
 * @method markProductsForExport(\App\Model\Product\Product[] $products)
 * @method \App\Model\Product\Product[] getProductsWithAvailability(\App\Model\Product\Availability\Availability $availability)
 * @method \App\Model\Product\Product[] getProductsWithParameter(\App\Model\Product\Parameter\Parameter $parameter)
 * @method \App\Model\Product\Product[] getProductsWithBrand(\App\Model\Product\Brand\Brand $brand)
 * @method \App\Model\Product\Product[] getProductsWithFlag(\App\Model\Product\Flag\Flag $flag)
 * @method \App\Model\Product\Product[] getProductsWithUnit(\App\Model\Product\Unit\Unit $unit)
 * @method \App\Model\Product\Product getSellableByUuid(string $uuid, int $domainId, \App\Model\Pricing\Group\PricingGroup $pricingGroup)
 * @property \App\Model\Product\ProductSellingDeniedRecalculator $productSellingDeniedRecalculator
 * @property \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade
 * @property \App\Model\Product\ProductHiddenRecalculator $productHiddenRecalculator
 */
class ProductFacade extends BaseProductFacade
{
    /**
     * @var \App\Model\Product\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \App\Model\Product\ProductVariantTropicFacade
     */
    private $productVariantTropicFacade;

    /**
     * @var \App\Model\Product\ProductDataFactory
     */
    private $productDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser
     */
    private $currentCustomerUser;

    /**
     * @var \App\Model\Product\StoreStock\ProductStoreStockFactory
     */
    private $productStoreStockFactory;

    /**
     * @var \App\Model\Store\StoreFacade
     */
    private $storeFacade;

    /**
     * @var \App\Component\GoogleApi\GoogleClient
     */
    private $googleClient;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \App\Component\Setting\Setting
     */
    private $setting;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \App\Model\Product\Group\ProductGroupFacade
     */
    private $productGroupFacade;

    /**
     * @var \App\Model\Product\Group\ProductGroupFactory
     */
    private $productGroupFactory;

    /**
     * @var \App\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \App\Model\Product\Flag\FlagFacade
     */
    private $flagFacade;

    /**
     * @var \App\Model\Product\Flag\ProductFlagFacade
     */
    private $productFlagFacade;

    /**
     * @var \App\Model\Product\Flag\ProductFlagDataFactory
     */
    private $productFlagDataFactory;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\ProductRepository $productRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade $productVisibilityFacade
     * @param \App\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculationScheduler $productPriceRecalculationScheduler
     * @param \App\Model\Pricing\Group\PricingGroupRepository $pricingGroupRepository
     * @param \App\Model\Product\Pricing\ProductManualInputPriceFacade $productManualInputPriceFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculationScheduler $productAvailabilityRecalculationScheduler
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \App\Model\Product\ProductHiddenRecalculator $productHiddenRecalculator
     * @param \App\Model\Product\ProductSellingDeniedRecalculator $productSellingDeniedRecalculator
     * @param \Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryRepository $productAccessoryRepository
     * @param \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade
     * @param \Shopsys\FrameworkBundle\Component\Plugin\PluginCrudExtensionFacade $pluginCrudExtensionFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductFactoryInterface $productFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryFactoryInterface $productAccessoryFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactoryInterface $productCategoryDomainFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueFactoryInterface $productParameterValueFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFactoryInterface $productVisibilityFactory
     * @param \App\Model\Product\Pricing\ProductPriceCalculation $productPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportScheduler $productExportScheduler
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \App\Model\Product\StoreStock\ProductStoreStockFactory $productStoreStockFactory
     * @param \App\Model\Store\StoreFacade $storeFacade
     * @param \App\Component\GoogleApi\GoogleClient $googleClient
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Component\Setting\Setting $setting
     * @param \Psr\Log\LoggerInterface $logger
     * @param \App\Model\Product\ProductVariantTropicFacade $productVariantTropicFacade
     * @param \App\Model\Product\ProductDataFactory $productDataFactory
     * @param \App\Model\Product\Group\ProductGroupFacade $productGroupFacade
     * @param \App\Model\Product\Group\ProductGroupFactory $productGroupFactory
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Model\Product\Flag\FlagFacade $flagFacade
     * @param \App\Model\Product\Flag\ProductFlagFacade $productFlagFacade
     * @param \App\Model\Product\Flag\ProductFlagDataFactory $productFlagDataFactory
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
        ProductExportScheduler $productExportScheduler,
        CurrentCustomerUser $currentCustomerUser,
        ProductStoreStockFactory $productStoreStockFactory,
        StoreFacade $storeFacade,
        GoogleClient $googleClient,
        PricingGroupFacade $pricingGroupFacade,
        Setting $setting,
        LoggerInterface $logger,
        ProductVariantTropicFacade $productVariantTropicFacade,
        ProductDataFactory $productDataFactory,
        ProductGroupFacade $productGroupFacade,
        ProductGroupFactory $productGroupFactory,
        CategoryFacade $categoryFacade,
        FlagFacade $flagFacade,
        ProductFlagFacade $productFlagFacade,
        ProductFlagDataFactory $productFlagDataFactory
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
            $productPriceCalculation,
            $productExportScheduler
        );

        $this->currentCustomerUser = $currentCustomerUser;
        $this->productStoreStockFactory = $productStoreStockFactory;
        $this->storeFacade = $storeFacade;
        $this->googleClient = $googleClient;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->setting = $setting;
        $this->logger = $logger;
        $this->productVariantTropicFacade = $productVariantTropicFacade;
        $this->productDataFactory = $productDataFactory;
        $this->productGroupFacade = $productGroupFacade;
        $this->productGroupFactory = $productGroupFactory;
        $this->categoryFacade = $categoryFacade;
        $this->flagFacade = $flagFacade;
        $this->productFlagFacade = $productFlagFacade;
        $this->productFlagDataFactory = $productFlagDataFactory;
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     * @return \App\Model\Product\Product
     */
    public function create(BaseProductData $productData)
    {
        $this->processSaleFlagAssignment($productData);
        try {
            $this->processAssignmentIntoSpecialCategories($productData);
        } catch (CategoryNotFoundException $exception) {
        }

        /** @var \App\Model\Product\Product $product */
        $product = parent::create($productData);
        $this->scheduleRecalculationsForMainVariant($product);
        $this->refreshMainProducts($product);

        $this->categoryFacade->refreshSpecialCategoriesVisibility();

        if ($product->isVariant()) {
            $this->refreshMainVariant($product->getMainVariant());
        }

        return $product;
    }

    /**
     * @param array $productIds
     * @return \App\Model\Product\Product[]
     */
    public function getVisibleMainVariantsByIds(array $productIds): array
    {
        return $this->productRepository->getVisibleMainVariantsByIds(
            $this->domain->getId(),
            $this->currentCustomerUser->getPricingGroup(),
            $productIds
        );
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Product\ProductData $productData
     */
    public function setAdditionalDataAfterCreate(BaseProduct $product, BaseProductData $productData): void
    {
        // Persist of ProductCategoryDomain requires known primary key of Product
        // @see https://github.com/doctrine/doctrine2/issues/4869
        $productCategoryDomains = $this->productCategoryDomainFactory->createMultiple($product, $productData->categoriesByDomainId);
        $product->setProductCategoryDomains($productCategoryDomains);

        if ($productData->pohodaProductType === Product::POHODA_PRODUCT_TYPE_ID_PRODUCT_GROUP) {
            $productData->outOfStockAction = Product::OUT_OF_STOCK_ACTION_EXCLUDE_FROM_SALE;
        }

        $this->em->flush($product);
        $this->productVariantTropicFacade->refreshVariantStatus($product, $productData->variantId);

        $this->saveParameters($product, $productData->parameters);
        $this->createProductVisibilities($product);
        $this->refreshProductManualInputPrices($product, $productData->manualInputPricesByPricingGroupId);
        $this->refreshProductAccessories($product, $productData->accessories);
        $this->refreshProductGroups($product, $productData->groupItems);
        $this->refreshProductFlags($product, $productData->flags);
        $this->productSellingDeniedRecalculator->calculateSellingDeniedForProduct($product);

        $this->imageFacade->manageImages($product, $productData->images);
        $this->friendlyUrlFacade->createFriendlyUrls('front_product_detail', $product->getId(), $product->getNames());

        $this->productAvailabilityRecalculationScheduler->scheduleProductForImmediateRecalculation($product);
        $this->productVisibilityFacade->refreshProductsVisibilityForMarkedDelayed();
        $this->productPriceRecalculationScheduler->scheduleProductForImmediateRecalculation($product);

        $this->updateProductStoreStocks($productData, $product);
        $this->updateMainProductsStoreStocks($product);

        $this->productSellingDeniedRecalculator->calculateSellingDeniedForProduct($product);
        $this->em->flush($product);
    }

    /**
     * @param int $productId
     * @param \App\Model\Product\ProductData $productData
     * @return \App\Model\Product\Product
     */
    public function edit($productId, BaseProductData $productData): Product
    {
        $product = $this->getById($productId);

        if ($productData->pohodaProductType === Product::POHODA_PRODUCT_TYPE_ID_PRODUCT_GROUP) {
            $productData->outOfStockAction = Product::OUT_OF_STOCK_ACTION_EXCLUDE_FROM_SALE;
        }

        $originalMainVariant = $product->isVariant() ? $product->getMainVariant() : null;

        if ($product->isMainVariant() && !$this->productVariantTropicFacade->isMainVariant($productData->variantId)) {
            $this->disconnectVariantsFromMainVariant($product);
        }

        $this->productVariantTropicFacade->refreshVariantStatus($product, $productData->variantId);
        $this->processSaleFlagAssignment($productData);
        try {
            $this->processAssignmentIntoSpecialCategories($productData);
        } catch (CategoryNotFoundException $exception) {
        }

        $this->refreshProductFlags($product, $productData->flags);
        parent::edit($productId, $productData);
        $this->refreshProductGroups($product, $productData->groupItems);
        $this->updateProductStoreStocks($productData, $product);
        $this->updateMainProductsStoreStocks($product);

        if ($product->isVariant()) {
            $this->productExportScheduler->scheduleRowIdForImmediateExport($product->getId());
            $originalMainVariant = $originalMainVariant === $product->getMainVariant() ? null : $originalMainVariant;
        }

        $this->scheduleRecalculationsForMainVariant($product);

        if ($originalMainVariant !== null) {
            $this->scheduleRecalculationsForMainVariant($originalMainVariant);
        }

        $this->productSellingDeniedRecalculator->calculateSellingDeniedForProduct($product);
        $this->em->flush($product);
        $this->refreshMainProducts($product);

        $this->categoryFacade->refreshSpecialCategoriesVisibility();

        if ($product->isVariant()) {
            $this->refreshMainVariant($product->getMainVariant());
        }

        return $product;
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     * @param \App\Model\Product\Product $product
     */
    private function updateProductStoreStocks(BaseProductData $productData, Product $product): void
    {
        $product->clearStoreStocks();
        $this->em->flush();

        if ($product->isPohodaProductTypeGroup()) {
            $internalStockId = $this->storeFacade->findByExternalNumber((string)PohodaProductExportRepository::POHODA_STOCK_TROPIC_ID)->getId();
            $stockQuantities = [$internalStockId => $this->getTheLowestStockQuantityFromProductGroups($product)];
        } else {
            $stockQuantities = $productData->stockQuantityByStoreId;
        }

        foreach ($stockQuantities as $storeId => $stockQuantity) {
            $storeStock = $this->productStoreStockFactory->create(
                $product,
                $this->storeFacade->getById($storeId),
                ($stockQuantity !== null && $stockQuantity >= 0) ? $stockQuantity : 0
            );

            $product->addStoreStock($storeStock);
        }

        $this->em->flush();

        $this->updateTotalProductStockQuantity($product);
    }

    /**
     * @param \App\Model\Product\Product $product
     */
    public function updateTotalProductStockQuantity(Product $product): void
    {
        $totalStockQuantity = 0;
        foreach ($product->getStocksWithoutZeroQuantityOnStore() as $productStoreStock) {
            $totalStockQuantity += $productStoreStock->getStockQuantity() ?? 0;
        }

        if ($totalStockQuantity < 0) {
            $totalStockQuantity = 0;
        }

        $product->setStockQuantity($totalStockQuantity);

        $realStockQuantity = $totalStockQuantity;
        if ($product->getStockQuantity() % $product->getAmountMultiplier() !== 0) {
            $realStockQuantity = (int)floor($product->getStockQuantity() / $product->getAmountMultiplier()) * $product->getAmountMultiplier();
        }
        $product->setRealStockQuantity($realStockQuantity);

        $this->em->flush($product);
    }

    /**
     * @param int $pohodaId
     * @return \App\Model\Product\Product|null
     */
    public function findByPohodaId(int $pohodaId): ?Product
    {
        return $this->productRepository->findByPohodaId($pohodaId);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Category\Category $category
     */
    public function removeProductCategoryDomainByProductAndCategory(Product $product, Category $category): void
    {
        $categoriesByDomainId = $product->getCategoriesIndexedByDomainId();
        $isSomeCategoryRemoveFromProduct = false;
        foreach ($this->domain->getAllIds() as $domainId) {
            $key = false;
            if (array_key_exists($domainId, $categoriesByDomainId)) {
                $key = array_search($category, $categoriesByDomainId[$domainId], true);
            }
            if ($key !== false) {
                unset($categoriesByDomainId[$domainId][$key]);
                $isSomeCategoryRemoveFromProduct = true;
            }
        }

        if ($isSomeCategoryRemoveFromProduct === true) {
            $productCategoryDomains = $this->productCategoryDomainFactory->createMultiple($product, $categoriesByDomainId);
            $product->editCategoriesByDomainId($productCategoryDomains);
        }
    }

    /**
     * @param int $limit
     * @param int $page
     * @return \App\Model\Product\Product[]
     */
    public function getWithEan(int $limit, int $page): array
    {
        return $this->productRepository->getWithEan($limit, $page);
    }

    /**
     * @param int $limit
     * @param int $page
     * @return \App\Model\Product\Product[]
     */
    public function getMainVariantsWithCatnum(int $limit, int $page): array
    {
        return $this->productRepository->getMainVariantsWithCatnum($limit, $page);
    }

    /**
     * @param string $ean
     * @return \App\Model\Product\Product|null
     */
    public function findOneNotMainVariantByEan(string $ean): ?Product
    {
        return $this->productRepository->findOneNotMainVariantByEan($ean);
    }

    /**
     * @param int $limit
     * @return \App\Model\Product\Product[]
     */
    public function getProductsForExportToMall(int $limit): array
    {
        $defaultPricingGroup = $this->pricingGroupFacade->getById(
            $this->setting->getForDomain(Setting::DEFAULT_PRICING_GROUP, DomainHelper::CZECH_DOMAIN)
        );

        return $this->productRepository->getProductsForExportToMall(
            $limit,
            DomainHelper::CZECH_DOMAIN,
            $defaultPricingGroup
        );
    }

    /**
     * @param \App\Model\Product\Product[] $products
     */
    public function markProductsAsExportedToMall(array $products): void
    {
        foreach ($products as $product) {
            $product->markProductAsExportedToMall();
        }

        $this->em->flush($products);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\Product[]
     */
    public function getVariantsForProductExportToMall(Product $product): array
    {
        $defaultPricingGroup = $this->pricingGroupFacade->getById(
            $this->setting->getForDomain(Setting::DEFAULT_PRICING_GROUP, DomainHelper::CZECH_DOMAIN)
        );

        return $this->productRepository->getVariantsForProductExportToMall(
            $product,
            DomainHelper::CZECH_DOMAIN,
            $defaultPricingGroup
        );
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Money\Money[]|null[] $manualInputPrices
     */
    protected function refreshProductManualInputPrices(BaseProduct $product, array $manualInputPrices)
    {
        foreach ($this->pricingGroupRepository->getAll() as $pricingGroup) {
            $this->productManualInputPriceFacade->refresh(
                $product,
                $pricingGroup,
                $manualInputPrices[$pricingGroup->getId()] ?? null
            );
        }
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Money\Money[]|null[] $manualInputPrices
     * @param int $domainId
     */
    public function refreshProductManualInputPricesForDomain(Product $product, array $manualInputPrices, int $domainId)
    {
        foreach ($this->pricingGroupRepository->getPricingGroupsByDomainId($domainId) as $pricingGroup) {
            $this->productManualInputPriceFacade->refresh(
                $product,
                $pricingGroup,
                $manualInputPrices[$pricingGroup->getId()] ?? null
            );
        }
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Component\GoogleApi\Youtube\YoutubeView[]
     */
    public function getYoutubeViews(Product $product): array
    {
        $youtubeDetails = [];
        $youtubeVideoIds = $product->getYoutubeVideoIds();
        if (!empty($youtubeVideoIds)) {
            foreach ($youtubeVideoIds as $youtubeVideoId) {
                try {
                    $youtubeResponse = $this->googleClient->getVideoList($youtubeVideoId);
                    if ($youtubeResponse->getPageInfo()->getTotalResults() > 0) {
                        /** @var \Google_Service_YouTube_Video $youtubeVideoItem */
                        $youtubeVideoItem = $youtubeResponse->getItems()[0];
                        $youtubeDetail = new YoutubeView(
                            $youtubeVideoId,
                            $youtubeVideoItem->getSnippet()->getThumbnails()->getDefault()->url,
                            $youtubeVideoItem->getSnippet()->getTitle()
                        );

                        $youtubeDetails[] = $youtubeDetail;
                    }
                } catch (Google_Service_Exception $googleServiceException) {
                    $this->logger->warning(
                        'Not showing Youtube video on product detail due to Google_Service_Exception',
                        [
                            'exception message' => $googleServiceException->getMessage(),
                            'productId' => $product->getId(),
                        ]
                    );
                }
            }
        }

        return $youtubeDetails;
    }

    /**
     * @param string $catnum
     * @return \App\Model\Product\Product[]
     */
    public function getByCatnum(string $catnum): array
    {
        return $this->productRepository->getByCatnum($catnum);
    }

    /**
     * @return \App\Model\Product\Product[]
     */
    public function getProductsToDeleteFromMall(): array
    {
        return $this->productRepository->getProductsToDeleteFromMall(
            DomainHelper::CZECH_DOMAIN
        );
    }

    /**
     * @param \App\Model\Product\Product $mainVariant
     * @param int $domainId
     * @throws \Shopsys\FrameworkBundle\Component\Setting\Exception\SettingValueNotFoundException
     * @return int
     */
    public function getCountOfVisibleVariantsForMainVariant(Product $mainVariant, int $domainId): int
    {
        $defaultPricingGroup = $this->pricingGroupFacade->getById(
            $this->setting->getForDomain(Setting::DEFAULT_PRICING_GROUP, DomainHelper::CZECH_DOMAIN)
        );

        return $this->productRepository->getCountOfVisibleVariantsForMainVariant(
            $mainVariant,
            $domainId,
            $defaultPricingGroup
        );
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @return \App\Model\Product\Product[]
     */
    public function getVisibleVariantsForProduct(Product $product, int $domainId): array
    {
        $defaultPricingGroup = $this->pricingGroupRepository->getById(
            $this->setting->getForDomain(Setting::DEFAULT_PRICING_GROUP, $domainId)
        );

        return $this->productRepository->getAllVisibleVariantsByMainVariant(
            $product,
            $domainId,
            $defaultPricingGroup
        );
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @return \App\Model\Product\Product[]
     */
    public function getSellableVariantsForProduct(Product $product, int $domainId): array
    {
        $defaultPricingGroup = $this->pricingGroupRepository->getById(
            $this->setting->getForDomain(Setting::DEFAULT_PRICING_GROUP, $domainId)
        );

        return $this->productRepository->getAllSellableVariantsByMainVariant(
            $product,
            $domainId,
            $defaultPricingGroup
        );
    }

    /**
     * @param string $parameterType
     * @param int $limit
     * @return \App\Model\Product\Product[]
     */
    public function getAllMainVariantProductsWithoutSkOrDeParameters(string $parameterType, int $limit): array
    {
        return $this->productRepository->getAllMainVariantProductsWithoutSkOrDeParameters($parameterType, $limit);
    }

    /**
     * @inheritDoc
     */
    public function saveParameters(BaseProduct $product, array $productParameterValuesData)
    {
        parent::saveParameters($product, $productParameterValuesData);
    }

    /**
     * @param int $domainId
     * @return int[]
     */
    public function hideVariantsWithDifferentPriceForDomain(int $domainId): array
    {
        $defaultPricingGroup = $this->pricingGroupRepository->getById(
            $this->setting->getForDomain(Setting::DEFAULT_PRICING_GROUP, $domainId)
        );

        $hiddenVariantsIds = [];

        $mainVariantIdsWithDifferentPrices = $this->productRepository->getMainVariantIdsWithDifferentPrice($domainId, $defaultPricingGroup);

        foreach ($mainVariantIdsWithDifferentPrices as $mainVariantIdWithDifferentPrices) {
            $variantsToHide = $this->productRepository->getVariantsWithDifferentPriceForMainVariant(
                $mainVariantIdWithDifferentPrices['mainVariantId'],
                Money::create($mainVariantIdWithDifferentPrices['defaultPrice']),
                $defaultPricingGroup
            );

            foreach ($variantsToHide as $variantToHide) {
                $hiddenVariantsIds[] = $variantToHide->getId();

                foreach ($this->domain->getAllIds() as $domainId) {
                    $variantToHide->setProductAsNotShown($domainId);
                }

                $this->em->flush($variantToHide);
            }
        }

        $this->productVisibilityFacade->refreshProductsVisibilityForMarked();
        return $hiddenVariantsIds;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param string $color
     */
    public function updateCzechProductNamesWithColor(Product $product, string $color): void
    {
        $product->updateCzechNamesWithColor($color);

        $this->em->flush($product);
    }

    /**
     * @param \App\Model\Product\Product|null $product
     * @param int $domainId
     * @return bool
     */
    public function isProductMarketable(?Product $product, int $domainId): bool
    {
        return $product !== null && $product->isShownOnDomain($domainId) && !$product->isSellingDenied() && !$product->getCalculatedSellingDenied();
    }

    /**
     * @param int $limit
     * @param int $page
     * @return \App\Model\Product\Product[]
     */
    public function getMainVariantsWithEan(int $limit, int $page): array
    {
        return $this->productRepository->getMainVariantsWithEan($limit, $page);
    }

    /**
     * @param int $id
     * @return \App\Model\Product\Product
     */
    public function getSellableById($id): Product
    {
        return $this->productRepository->getSellableById($id, $this->domain->getId(), $this->currentCustomerUser->getPricingGroup());
    }

    /**
     * @param array $products
     * @param int $domainId
     * @return \App\Model\Product\Product[][][]
     */
    public function getVariantsIndexedByPricingGroupIdAndMainVariantId(array $products, int $domainId): array
    {
        $variantsIndexedByPricingGroupIdAndMainVariantId = [];
        foreach ($this->pricingGroupFacade->getByDomainId($domainId) as $pricingGroup) {
            $variantsIndexedByPricingGroupIdAndMainVariantId[$pricingGroup->getId()] = $this->productRepository->getVariantsIndexedByMainVariantId(
                $products,
                $domainId,
                $pricingGroup
            );
        }

        return $variantsIndexedByPricingGroupIdAndMainVariantId;
    }

    /**
     * @param int[] $brandIds
     * @return array(int,\App\Model\Product\Product[])
     */
    public function getByBrandIdsIndexedById(array $brandIds): array
    {
        $products = $this->productRepository->getByBrandIds($brandIds);
        $indexedProducts = [];

        array_walk($products, function (Product $product) use (&$indexedProducts) {
            $indexedProducts[$product->getId()] = $product;
        });

        return $indexedProducts;
    }

    /**
     * @param int[] $categoryIds
     * @return array(int,\App\Model\Product\Product[])
     */
    public function getByCategoryIdsIndexedById(array $categoryIds): array
    {
        $products = [];
        $indexedProducts = [];

        try {
            $domainIds = [$this->domain->getId()];
        } catch (NoDomainSelectedException $exception) {
            $domainIds = $this->domain->getAllIds();
        }

        foreach ($domainIds as $domainId) {
            $products = array_merge($products, $this->productRepository->getByCategoryIds($categoryIds, $domainId));
        }

        array_walk($products, function (Product $product) use (&$indexedProducts) {
            $indexedProducts[$product->getId()] = $product;
        });

        return $indexedProducts;
    }

    /**
     * @param int[] $productIds
     * @return array(int,\App\Model\Product\Product[])
     */
    public function getByIdsIndexedById(array $productIds): array
    {
        $products = $this->productRepository->getAllByIds($productIds);
        $indexedProducts = [];

        array_walk($products, function (Product $product) use (&$indexedProducts) {
            $indexedProducts[$product->getId()] = $product;
        });

        return $indexedProducts;
    }

    /**
     * @inheritDoc
     */
    public function getAllProductSellingPricesIndexedByDomainId(BaseProduct $product)
    {
        $sellingPricesByDomainId = parent::getAllProductSellingPricesIndexedByDomainId($product);

        foreach ($sellingPricesByDomainId as &$sellingPrices) {
            usort($sellingPrices, function (ProductSellingPrice $first, ProductSellingPrice $second) {
                /** @var \App\Model\Pricing\Group\PricingGroup $firstPricingGroup */
                $firstPricingGroup = $first->getPricingGroup();
                /** @var \App\Model\Pricing\Group\PricingGroup $secondPricingGroup */
                $secondPricingGroup = $second->getPricingGroup();
                $isFirstOrdinary = $firstPricingGroup->isOrdinaryCustomerPricingGroup();
                $isSecondOrdinary = $secondPricingGroup->isOrdinaryCustomerPricingGroup();

                if ($isFirstOrdinary && !$isSecondOrdinary) {
                    return -1;
                }

                if (!$isFirstOrdinary && $isSecondOrdinary) {
                    return 1;
                }

                return 0;
            });
        }

        return $sellingPricesByDomainId;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @param string $locale
     * @return string[][]
     */
    public function getProductGiftNames(Product $product, int $domainId, string $locale): array
    {
        $gifts = $product->getGifts($domainId);
        $giftNames = [];
        foreach ($gifts as $gift) {
            $giftNames[] = [
                'name' => $gift->getName($locale),
            ];
        }

        return $giftNames;
    }

    /**
     * @param int $productId
     */
    public function delete($productId)
    {
        $product = $this->getById($productId);

        $mainVariant = null;
        if ($product->isMainVariant()) {
            $this->disconnectVariantsFromMainVariant($product);
        } elseif ($product->isVariant()) {
            $mainVariant = $product->getMainVariant();
        }
        parent::delete($productId);
        if ($mainVariant !== null) {
            $this->refreshMainVariant($mainVariant);
        }
    }

    /**
     * @param \App\Model\Product\Product $mainVariant
     */
    private function disconnectVariantsFromMainVariant(Product $mainVariant): void
    {
        foreach ($mainVariant->getVariants() as $variant) {
            $variantData = $this->productDataFactory->createFromProduct($variant);
            $variantData->variantId = null;

            foreach ($this->domain->getAllIds() as $domainId) {
                $variantData->shown[$domainId] = false;
            }

            $this->edit($variant->getId(), $variantData);
        }
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param array $groupItems
     */
    private function refreshProductGroups(Product $product, array $groupItems): void
    {
        $oldGroupItems = $this->productGroupFacade->getAllByMainProduct($product);
        foreach ($oldGroupItems as $oldGroupItem) {
            $this->em->remove($oldGroupItem);
        }
        $this->em->flush($oldGroupItems);

        $toFlush = [];
        foreach ($groupItems as $groupItemArray) {
            if (isset($groupItemArray['item'], $groupItemArray['item_count'])) {
                $groupItem = $this->productGroupFactory->create($product, $groupItemArray['item'], $groupItemArray['item_count']);
                $this->em->persist($groupItem);
                $product->addProductGroup($groupItem);
                $toFlush[] = $groupItem;
            }
        }

        if (count($toFlush) > 0) {
            $this->em->flush($toFlush);
        }
    }

    /**
     * @param \App\Model\Product\Product $product
     */
    private function scheduleRecalculationsForMainVariant(Product $product)
    {
        $mainVariant = $product->isVariant() ? $product->getMainVariant() : $product;
        $mainVariant->markForVisibilityRecalculation();
        $this->productAvailabilityRecalculationScheduler->scheduleProductForImmediateRecalculation($mainVariant);
        $this->productVisibilityFacade->refreshProductsVisibilityForMarkedDelayed();
        $this->productPriceRecalculationScheduler->scheduleProductForImmediateRecalculation($mainVariant);
        $this->productExportScheduler->scheduleRowIdForImmediateExport($mainVariant->getId());
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     * @return \App\Model\Product\Flag\Flag[]
     */
    private function getSpecialCategoryFlags(ProductData $productData): array
    {
        $flags = [];

        foreach ($productData->flags as $productFlag) {
            if ($productFlag->isActive() && $productFlag->flag->isSpecial() && !in_array($productFlag->flag, $flags, true)) {
                $flags[] = $productFlag->flag;
            }
        }

        foreach ($productData->variants as $variant) {
            $flags = array_merge($flags, $this->getSpecialCategoryFlagsFromVariant($variant));
        }

        return array_unique($flags, SORT_REGULAR);
    }

    /**
     * @param \App\Model\Product\Product $variant
     * @return \App\Model\Product\Flag\Flag[]
     */
    private function getSpecialCategoryFlagsFromVariant(Product $variant): array
    {
        $flags = [];

        foreach ($variant->getActiveFlags() as $flag) {
            if ($flag->isSpecial() && !in_array($flag, $flags, true)) {
                $flags[] = $flag;
            }
        }

        return $flags;
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     */
    private function processAssignmentIntoSpecialCategories(ProductData $productData): void
    {
        $saleCategory = $this->categoryFacade->getSaleCategory();
        $newsCategory = $this->categoryFacade->getNewsCategory();
        $hasSaleFlag = false;
        $hasNewsFlag = false;

        foreach ($this->getSpecialCategoryFlags($productData) as $specialCategoryFlag) {
            $hasSaleFlag = $hasSaleFlag || $specialCategoryFlag->isSale();
            $hasNewsFlag = $hasNewsFlag || $specialCategoryFlag->isNews();
        }

        foreach ($this->domain->getAllIds() as $domainId) {
            if ($hasSaleFlag && !in_array($saleCategory, $productData->categoriesByDomainId[$domainId], true)) {
                $productData->categoriesByDomainId[$domainId][] = $saleCategory;
            }

            if ($hasNewsFlag && !in_array($newsCategory, $productData->categoriesByDomainId[$domainId], true)) {
                $productData->categoriesByDomainId[$domainId][] = $newsCategory;
            }
        }

        foreach ($productData->categoriesByDomainId as $domainId => $categories) {
            foreach ($categories as $index => $category) {
                if (!$hasSaleFlag && $category->getId() === $saleCategory->getId()) {
                    unset($productData->categoriesByDomainId[$domainId][$index]);
                }

                if (!$hasNewsFlag && $category->getId() === $newsCategory->getId()) {
                    unset($productData->categoriesByDomainId[$domainId][$index]);
                }
            }
        }
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return int
     */
    private function getTheLowestStockQuantityFromProductGroups(Product $product): int
    {
        $bigStockQuantityPlaceholder = 9999999;
        $lowestStockQuantity = $bigStockQuantityPlaceholder;
        $groupStockQuantities = [];

        foreach ($product->getProductGroups() as $productGroup) {
            $groupStockQuantities[] = $this->productGroupFacade->getStockQuantity($productGroup);
        }

        foreach ($groupStockQuantities as $groupStockQuantity) {
            $lowestStockQuantity = $lowestStockQuantity > $groupStockQuantity ? $groupStockQuantity : $lowestStockQuantity;
        }

        return $lowestStockQuantity === $bigStockQuantityPlaceholder ? 0 : $lowestStockQuantity;
    }

    /**
     * @param \App\Model\Product\Product $product
     */
    private function updateMainProductsStoreStocks(Product $product): void
    {
        $productGroups = $this->productGroupFacade->getAllByItem($product);

        foreach ($productGroups as $productGroup) {
            $this->updateProductStoreStocks($this->productDataFactory->createFromProduct($productGroup->getMainProduct()), $productGroup->getMainProduct());
        }
    }

    /**
     * @param \App\Model\Product\Product $product
     */
    private function refreshMainProducts(Product $product): void
    {
        foreach ($this->productGroupFacade->getAllByItem($product) as $productGroup) {
            $this->em->refresh($productGroup->getMainProduct());
            $this->edit($productGroup->getMainProduct()->getId(), $this->productDataFactory->createFromProduct($productGroup->getMainProduct()));
        }
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     */
    private function processSaleFlagAssignment(ProductData $productData): void
    {
        $isInAnySaleStock = false;
        if ($this->productVariantTropicFacade->isMainVariant($productData->variantId)) {
            $variants = $this->productVariantTropicFacade->getVariantsByMainVariantId($productData->variantId);
            foreach ($variants as $variant) {
                if ($variant->isInAnySaleStock()) {
                    $isInAnySaleStock = true;
                    break;
                }
            }
        } else {
            $isInAnySaleStock = $this->getQuantityInSaleStocks($productData) > 0;
        }

        $productFlagsData = $productData->flags;

        if (!$isInAnySaleStock) {
            $productFlagsData = array_filter($productFlagsData, function (ProductFlagData $productFlagData) {
                return !$productFlagData->flag->isSale();
            });
        } else {
            foreach ($this->flagFacade->getSaleFlags() as $saleFlag) {
                foreach ($productFlagsData as $productFlagData) {
                    if ($productFlagData->flag->getId() === $saleFlag->getId()) {
                        continue 2;
                    }
                }

                $productFlagsData[] = $this->productFlagDataFactory->create($saleFlag);
            }
        }

        $productData->flags = $productFlagsData;
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     * @return int
     */
    private function getQuantityInSaleStocks(ProductData $productData): int
    {
        $storesIndexedById = $this->storeFacade->getAll();
        $quantityInSaleStocks = 0;
        foreach ($productData->stockQuantityByStoreId as $storeId => $stockQuantity) {
            $store = $storesIndexedById[$storeId] ?? null;
            if ($stockQuantity !== null && $stockQuantity > 0 && $store !== null && $store->isSaleStock()) {
                $quantityInSaleStocks += $stockQuantity;
            }
        }

        return $quantityInSaleStocks;
    }

    /**
     * @param \App\Model\Product\Product $mainVariant
     */
    private function refreshMainVariant(Product $mainVariant): void
    {
        $mainVariantData = $this->productDataFactory->createFromProduct($mainVariant);
        $this->edit($mainVariant->getId(), $mainVariantData);
    }

    /**
     * @return \App\Model\Product\Product[]
     */
    public function getProductsForRefresh(): array
    {
        return $this->productRepository->getProductsForRefresh();
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Product\Flag\ProductFlagData[] $productFlagsData
     */
    private function refreshProductFlags(BaseProduct $product, array $productFlagsData)
    {
        $oldProductFlags = $this->productFlagFacade->getByProduct($product);

        foreach ($oldProductFlags as $oldProductFlag) {
            $this->em->remove($oldProductFlag);
        }

        $this->em->flush($oldProductFlags);
        $product->clearProductFlags();
        $this->em->flush($product);
        $processedFlags = [];

        foreach ($productFlagsData as $productFlagData) {
            if (!in_array($productFlagData->flag, $processedFlags, true)) {
                $this->productFlagFacade->create($productFlagData, $product);
                $processedFlags[] = $productFlagData->flag;
            }
        }
    }

    /**
     * @return \App\Model\Product\ProductDomain[]
     */
    public function getProductDomainsForDescriptionTranslation(): array
    {
        return $this->productRepository->getProductDomainsForDescriptionTranslation();
    }

    /**
     * @return \App\Model\Product\ProductDomain[]
     */
    public function getProductDomainsForShortDescriptionTranslation(): array
    {
        return $this->productRepository->getProductDomainsForShortDescriptionTranslation();
    }

    /**
     * @param \DateTime|null $dateTime
     * @return int[]
     */
    public function getPohodaIdsForProductsUpdatedSince(?DateTime $dateTime): array
    {
        return $this->productRepository->getPohodaIdsForProductsUpdatedSince($dateTime);
    }
}
