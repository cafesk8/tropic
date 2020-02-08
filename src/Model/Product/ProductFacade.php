<?php

declare(strict_types=1);

namespace App\Model\Product;

use Doctrine\ORM\EntityManagerInterface;
use Google_Service_Exception;
use Psr\Log\LoggerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Component\Plugin\PluginCrudExtensionFacade;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser;
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
use Shopsys\FrameworkBundle\Model\Product\Search\Export\ProductSearchExportScheduler;
use App\Component\Domain\DomainHelper;
use App\Component\GoogleApi\GoogleClient;
use App\Component\GoogleApi\Youtube\YoutubeView;
use App\Component\Setting\Setting;
use App\Model\Category\Category;
use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Product\MainVariantGroup\MainVariantGroup;
use App\Model\Product\Parameter\ParameterFacade;
use App\Model\Product\Product as ChildProduct;
use App\Model\Product\StoreStock\ProductStoreStockFactory;
use App\Model\Store\StoreFacade;

/**
 * @property \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
 * @property \App\Model\Product\Parameter\ParameterRepository $parameterRepository
 * @property \App\Component\Image\ImageFacade $imageFacade
 * @property \App\Model\Pricing\Group\PricingGroupRepository $pricingGroupRepository
 * @property \App\Model\Product\Pricing\ProductManualInputPriceFacade $productManualInputPriceFacade
 * @property \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
 * @property \App\Model\Product\Pricing\ProductPriceCalculation $productPriceCalculation
 * @method \App\Model\Product\Product getById(int $productId)
 * @method \App\Model\Product\Product create(\App\Model\Product\ProductData $productData)
 * @method \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductSellingPrice[][] getAllProductSellingPricesIndexedByDomainId(\App\Model\Product\Product $product)
 * @method \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductSellingPrice[] getAllProductSellingPricesByDomainId(\App\Model\Product\Product $product, int $domainId)
 * @method createProductVisibilities(\App\Model\Product\Product $product)
 * @method refreshProductAccessories(\App\Model\Product\Product $product, \App\Model\Product\Product[] $accessories)
 * @method \App\Model\Product\Product getOneByCatnumExcludeMainVariants(string $productCatnum)
 * @method \App\Model\Product\Product getByUuid(string $uuid)
 */
class ProductFacade extends BaseProductFacade
{
    /**
     * @var \App\Model\Product\ProductRepository
     */
    protected $productRepository;

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
     * @var \App\Model\Product\CachedProductDistinguishingParameterValueFacade
     */
    private $cachedProductDistinguishingParameterValueFacade;

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
     * @param \App\Model\Product\Pricing\ProductPriceCalculation $productPriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Product\Search\Export\ProductSearchExportScheduler $productSearchExportScheduler
     * @param \Shopsys\FrameworkBundle\Model\Customer\User\CurrentCustomerUser $currentCustomerUser
     * @param \App\Model\Product\StoreStock\ProductStoreStockFactory $productStoreStockFactory
     * @param \App\Model\Store\StoreFacade $storeFacade
     * @param \App\Model\Product\CachedProductDistinguishingParameterValueFacade $cachedProductDistinguishingParameterValueFacade
     * @param \App\Component\GoogleApi\GoogleClient $googleClient
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Component\Setting\Setting $setting
     * @param \Psr\Log\LoggerInterface $logger
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
        ProductSearchExportScheduler $productSearchExportScheduler,
        CurrentCustomerUser $currentCustomerUser,
        ProductStoreStockFactory $productStoreStockFactory,
        StoreFacade $storeFacade,
        CachedProductDistinguishingParameterValueFacade $cachedProductDistinguishingParameterValueFacade,
        GoogleClient $googleClient,
        PricingGroupFacade $pricingGroupFacade,
        Setting $setting,
        LoggerInterface $logger
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
            $productSearchExportScheduler
        );

        $this->currentCustomerUser = $currentCustomerUser;
        $this->productStoreStockFactory = $productStoreStockFactory;
        $this->storeFacade = $storeFacade;
        $this->cachedProductDistinguishingParameterValueFacade = $cachedProductDistinguishingParameterValueFacade;
        $this->googleClient = $googleClient;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->setting = $setting;
        $this->logger = $logger;
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
    public function setAdditionalDataAfterCreate(BaseProduct $product, ProductData $productData): void
    {
        parent::setAdditionalDataAfterCreate($product, $productData);

        $this->updateProductStoreStocks($productData, $product);
    }

    /**
     * @param int $productId
     * @param \App\Model\Product\ProductData $productData
     * @return \App\Model\Product\Product
     */
    public function edit($productId, ProductData $productData): Product
    {
        /** @var \App\Model\Product\Product $product */
        $product = parent::edit($productId, $productData);

        $this->updateProductStoreStocks($productData, $product);
        $this->updateMainVariantGroup($productData, $product);

        $this->cachedProductDistinguishingParameterValueFacade->invalidCacheByProduct($product);

        return $product;
    }

    /**
     * @param \App\Model\Product\Parameter\Parameter $parameter
     * @return \App\Model\Product\Product[]
     */
    public function getProductsWithDistinguishingParameter(Parameter $parameter): array
    {
        return $this->productRepository->getProductsWithDistinguishingParameter($parameter);
    }

    /**
     * @param \App\Model\Product\Product $mainVariant
     */
    public function flushMainVariant(Product $mainVariant): void
    {
        $toFlush = $mainVariant->getVariants();
        $toFlush[] = $mainVariant;
        $this->em->flush($toFlush);
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     * @param \App\Model\Product\Product $product
     */
    private function updateProductStoreStocks(ProductData $productData, Product $product): void
    {
        $product->clearStoreStocks();
        $this->em->flush();

        foreach ($productData->stockQuantityByStoreId as $storeId => $stockQuantity) {
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
    private function updateTotalProductStockQuantity(Product $product): void
    {
        $totalStockQuantity = 0;
        foreach ($product->getStocksWithoutZeroQuantityOnStore() as $productStoreStock) {
            $totalStockQuantity += $productStoreStock->getStockQuantity() ?? 0;
        }

        $totalStockQuantity -= ChildProduct::DECREASE_REAL_STOCK_QUANTITY_BY;

        if ($totalStockQuantity < 0) {
            $totalStockQuantity = 0;
        }

        $product->setStockQuantity($totalStockQuantity);
        $this->em->flush($product);

        $this->productHiddenRecalculator->calculateHiddenForProduct($product);
        $this->productSellingDeniedRecalculator->calculateSellingDeniedForProduct($product);
        $this->em->flush($product);
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     * @param \App\Model\Product\Product $product
     */
    private function updateMainVariantGroup(ProductData $productData, Product $product): void
    {
        $mainVariantGroup = $product->getMainVariantGroup();

        if ($mainVariantGroup === null) {
            return;
        }

        $mainVariantGroup->setDistinguishingParameter($productData->distinguishingParameterForMainVariantGroup);
        $mainVariantGroup->addProducts(array_merge($productData->productsInGroup, [$product]));
        $this->em->flush();
    }

    /**
     * @param string $transferNumber
     * @return \App\Model\Product\Product|null
     */
    public function findByTransferNumber(string $transferNumber): ?Product
    {
        return $this->productRepository->findByTransferNumber($transferNumber);
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
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $actionPrice
     * @param int $domainId
     */
    public function setActionPriceForProduct(Product $product, ?Money $actionPrice, int $domainId): void
    {
        $product->setActionPrice($actionPrice, $domainId);
        $this->em->flush();
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
     * @param \App\Model\Product\MainVariantGroup\MainVariantGroup $mainVariantGroup
     * @param int $domainId
     * @return \App\Model\Product\Product[]
     */
    public function getVariantsForMainVariantGroup(MainVariantGroup $mainVariantGroup, int $domainId): array
    {
        $defaultPricingGroup = $this->pricingGroupFacade->getById(
            $this->setting->getForDomain(Setting::DEFAULT_PRICING_GROUP, DomainHelper::CZECH_DOMAIN)
        );

        return $this->productRepository->getVariantsForMainVariantGroup(
            $mainVariantGroup,
            $domainId,
            $defaultPricingGroup
        );
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Money\Money[]|null[] $manualInputPrices
     */
    protected function refreshProductManualInputPrices(Product $product, array $manualInputPrices)
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
     * @param \App\Model\Product\Product[] $products
     * @return \App\Component\GoogleApi\Youtube\YoutubeView[][]
     */
    public function getYoutubeViewForMainVariants(array $products): array
    {
        $youtubeViewForMainVariants = [];

        foreach ($products as $product) {
            $youtubeViewForMainVariants[$product->getId()] = $this->getYoutubeViews($product);
        }

        return $youtubeViewForMainVariants;
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
    public function getVariantsForProduct(Product $product, int $domainId): array
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
                $variantToHide->setProductAsHidden();
                $this->em->flush($variantToHide);
                $this->productHiddenRecalculator->calculateHiddenForProduct($variantToHide);
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
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Product\Parameter\ParameterFacade $parameterFacade
     */
    public function fillVariantNamesFromMainVariantNames(Product $product, ParameterFacade $parameterFacade): void
    {
        if ($product->isMainVariant() === false) {
            return;
        }

        $namesByLocale = $product->getNames();

        /** @var \App\Model\Product\Product $variant */
        foreach ($product->getVariants() as $variant) {
            $variantSizeParameterValue = $parameterFacade->findSizeProductParameterValueByProductId($variant->getId());
            if ($variantSizeParameterValue === null) {
                return;
            }

            foreach ($namesByLocale as $locale => $name) {
                if ($name !== null) {
                    $variant->updateNameWithSize(
                        $locale,
                        $name,
                        $variantSizeParameterValue->getValue()->getText()
                    );
                }
            }

            $this->em->flush();
            $this->friendlyUrlFacade->createFriendlyUrls('front_product_detail', $variant->getId(), $variant->getNames());
        }
    }

    /**
     * @param \App\Model\Product\Product|null $product
     * @return bool
     */
    public function isProductMarketable(?Product $product): bool
    {
        return $product !== null && $product->isHidden() === false && $product->getCalculatedHidden() === false &&
            $product->isSellingDenied() === false && $product->getCalculatedSellingDenied() === false;
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
    public function getSellableById($id): ChildProduct
    {
        /** @var \App\Model\Product\Product $product */
        $product = $this->productRepository->getSellableById($id, $this->domain->getId(), $this->currentCustomer->getPricingGroup());

        return $product;
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
     * @param array $brandIds
     * @return array
     */
    public function getIdsByBrandIds(array $brandIds): array
    {
        return $this->productRepository->getIdsByBrandIds($brandIds);
    }

    /**
     * @param array $categoryIds
     * @return array
     */
    public function getIdsByCategoryIds(array $categoryIds): array
    {
        return $this->productRepository->getIdsByCategoryIds($categoryIds, $this->domain->getId());
    }
}
