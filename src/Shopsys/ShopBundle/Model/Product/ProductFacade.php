<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

use Doctrine\ORM\EntityManagerInterface;
use Google_Service_Exception;
use Psr\Log\LoggerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Component\Plugin\PluginCrudExtensionFacade;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
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
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Component\GoogleApi\GoogleClient;
use Shopsys\ShopBundle\Component\GoogleApi\Youtube\YoutubeView;
use Shopsys\ShopBundle\Component\Setting\Setting;
use Shopsys\ShopBundle\Model\Category\Category;
use Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade;
use Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade;
use Shopsys\ShopBundle\Model\Product\Product as ChildProduct;
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
     * @var \Shopsys\ShopBundle\Model\Product\CachedProductDistinguishingParameterValueFacade
     */
    private $cachedProductDistinguishingParameterValueFacade;

    /**
     * @var \Shopsys\ShopBundle\Component\GoogleApi\GoogleClient
     */
    private $googleClient;

    /**
     * @var \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Setting\Setting
     */
    private $setting;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

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
     * @param \Shopsys\ShopBundle\Model\Product\CachedProductDistinguishingParameterValueFacade $cachedProductDistinguishingParameterValueFacade
     * @param \Shopsys\ShopBundle\Component\GoogleApi\GoogleClient $googleClient
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \Shopsys\ShopBundle\Component\Setting\Setting $setting
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
        CurrentCustomer $currentCustomer,
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
            $productPriceCalculation
        );

        $this->currentCustomer = $currentCustomer;
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
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getVisibleMainVariantsByIds(array $productIds): array
    {
        return $this->productRepository->getVisibleMainVariantsByIds(
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
     * @param \Shopsys\ShopBundle\Model\Product\Product $mainVariant
     */
    public function flushMainVariant(Product $mainVariant): void
    {
        $toFlush = $mainVariant->getVariants();
        $toFlush[] = $mainVariant;
        $this->em->flush($toFlush);
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
                ($stockQuantity !== null && $stockQuantity >= 0) ? $stockQuantity : 0
            );

            $product->addStoreStock($storeStock);
        }

        $this->em->flush();

        $this->updateTotalProductStockQuantity($product);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
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
        $mainVariantGroup->addProducts(array_merge($productData->productsInGroup, [$product]));
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
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Shopsys\ShopBundle\Model\Category\Category $category
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
            $product->editCategoriesByDomainId($this->productCategoryDomainFactory, $categoriesByDomainId);
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
     * @param int $limit
     * @param int $page
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getMainVariantsWithCatnum(int $limit, int $page): array
    {
        return $this->productRepository->getMainVariantsWithCatnum($limit, $page);
    }

    /**
     * @param string $ean
     * @return \Shopsys\ShopBundle\Model\Product\Product|null
     */
    public function findOneNotMainVariantByEan(string $ean): ?Product
    {
        return $this->productRepository->findOneNotMainVariantByEan($ean);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
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
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
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
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $products
     */
    public function markProductsAsExportedToMall(array $products): void
    {
        foreach ($products as $product) {
            $product->markProductAsExportedToMall();
        }

        $this->em->flush($products);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
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
     * @param \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup $mainVariantGroup
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
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
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
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
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
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
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\ShopBundle\Component\GoogleApi\Youtube\YoutubeView|null
     */
    public function getYoutubeView(Product $product): ?YoutubeView
    {
        $youtubeDetail = null;
        if ($product->getYoutubeVideoId() !== null) {
            try {
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

    /**
     * @param string $catnum
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getByCatnum(string $catnum): array
    {
        return $this->productRepository->getByCatnum($catnum);
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
     */
    public function getProductsToDeleteFromMall(): array
    {
        return $this->productRepository->getProductsToDeleteFromMall(
            DomainHelper::CZECH_DOMAIN
        );
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $mainVariant
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
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param int $domainId
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
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
     * @return \Shopsys\ShopBundle\Model\Product\Product[]
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
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param string $color
     */
    public function updateCzechProductNamesWithColor(Product $product, string $color): void
    {
        $product->updateCzechNamesWithColor($color);

        $this->em->flush($product);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade $parameterFacade
     */
    public function fillVariantNamesFromMainVariantNames(Product $product, ParameterFacade $parameterFacade): void
    {
        if ($product->isMainVariant() === false) {
            return;
        }

        $namesByLocale = $product->getNames();

        /** @var \Shopsys\ShopBundle\Model\Product\Product $variant */
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
     * @param \Shopsys\ShopBundle\Model\Product\Product|null $product
     * @return bool
     */
    public function isProductMarketable(?Product $product): bool
    {
        return $product !== null && $product->isHidden() === false && $product->getCalculatedHidden() === false &&
            $product->isSellingDenied() === false && $product->getCalculatedSellingDenied() === false;
    }
}
