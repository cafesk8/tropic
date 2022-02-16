<?php

declare(strict_types=1);

namespace App\Model\Product\Elasticsearch;

use App\Model\Category\CategoryFacade;
use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Product\Availability\AvailabilityFacade;
use App\Model\Product\Availability\ProductAvailabilityRecalculator;
use App\Model\Product\Flag\Flag;
use App\Model\Product\Flag\FlagFacade;
use App\Model\Product\Product;
use App\Model\Product\ProductSellingDeniedRecalculator;
use App\Model\Product\Set\ProductSetFacade;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlRepository;
use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportRepository as BaseProductExportRepository;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\FrameworkBundle\Model\Product\ProductFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibility;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityRepository;

/**
 * @property \App\Model\Product\Parameter\ParameterRepository $parameterRepository
 * @property \App\Component\Router\FriendlyUrl\FriendlyUrlRepository $friendlyUrlRepository
 * @property \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
 * @method string extractDetailUrl(int $domainId, \App\Model\Product\Product $product)
 * @method array extractVisibility(int $domainId, \App\Model\Product\Product $product)
 * @method int[] extractVariantIds(\App\Model\Product\Product $product)
 * @property \App\Model\Product\ProductVisibilityRepository $productVisibilityRepository
 * @property \App\Model\Product\ProductFacade $productFacade
 * @method int[] extractCategories(int $domainId, \App\Model\Product\Product $product)
 */
class ProductExportRepository extends BaseProductExportRepository
{
    public const SCOPE_STOCKS = 'stocks';
    public const SCOPE_URLS = 'urls';

    private PricingGroupFacade $pricingGroupFacade;

    private ProductSetFacade $productSetFacade;

    private array $variantsCachedPrices = [];

    private array $cachedParameters = [];

    private FlagFacade $flagFacade;

    private CategoryFacade $categoryFacade;

    private ProductAvailabilityRecalculator $productAvailabilityRecalculator;

    private ProductSellingDeniedRecalculator $productSellingDeniedRecalculator;

    private AvailabilityFacade $availabilityFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlRepository $friendlyUrlRepository
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Product\ProductVisibilityRepository $productVisibilityRepository
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \App\Model\Product\Set\ProductSetFacade $productSetFacade
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Product\Flag\FlagFacade $flagFacade
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Model\Product\Availability\ProductAvailabilityRecalculator $productAvailabilityRecalculator
     * @param \App\Model\Product\ProductSellingDeniedRecalculator $productSellingDeniedRecalculator
     * @param \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        ParameterRepository $parameterRepository,
        ProductFacade $productFacade,
        FriendlyUrlRepository $friendlyUrlRepository,
        Domain $domain,
        ProductVisibilityRepository $productVisibilityRepository,
        FriendlyUrlFacade $friendlyUrlFacade,
        ProductSetFacade $productSetFacade,
        PricingGroupFacade $pricingGroupFacade,
        FlagFacade $flagFacade,
        CategoryFacade $categoryFacade,
        ProductAvailabilityRecalculator $productAvailabilityRecalculator,
        ProductSellingDeniedRecalculator $productSellingDeniedRecalculator,
        AvailabilityFacade $availabilityFacade
    ) {
        parent::__construct($em, $parameterRepository, $productFacade, $friendlyUrlRepository, $domain, $productVisibilityRepository, $friendlyUrlFacade);
        $this->productSetFacade = $productSetFacade;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->flagFacade = $flagFacade;
        $this->categoryFacade = $categoryFacade;
        $this->productAvailabilityRecalculator = $productAvailabilityRecalculator;
        $this->productSellingDeniedRecalculator = $productSellingDeniedRecalculator;
        $this->availabilityFacade = $availabilityFacade;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @param string $locale
     * @param string|null $scope
     * @return array
     */
    protected function extractResult(BaseProduct $product, int $domainId, string $locale, ?string $scope = null): array
    {
        if ($scope === self::SCOPE_STOCKS) {
            $this->productSellingDeniedRecalculator->calculateSellingDeniedForProduct($product);
            $this->productAvailabilityRecalculator->recalculateOneProductAvailability($product);
            $result['in_stock'] = $this->extractImmediateAvailability($product, $locale);
            $result['available'] = $this->extractAvailability($product, $locale);
            $result['availability'] = $this->availabilityFacade->getAvailabilityText($product, $locale);
            $result['availability_color'] = $product->getCalculatedAvailability()->getRgbColor();
            $result['real_sale_stocks_quantity'] = $product->isSellingDenied() || $product->isMainVariant() ? 0 : $product->getRealSaleStocksQuantity();
            $result['stock_quantity'] = $product->getStockQuantity();
            $result['internal_stocks_quantity'] = $product->getBiggestVariantRealInternalStockQuantity();
            $result['external_stocks_quantity'] = $product->getBiggestVariantRealExternalStockQuantity();

            return $result;
        }

        if ($scope === self::SCOPE_URLS) {
            $result['detail_url'] = $this->extractDetailUrl($domainId, $product);

            return $result;
        }

        $variants = $this->productFacade->getVisibleVariantsForProduct($product, $domainId);
        $result = parent::extractResult($product, $domainId, $locale);

        $result['variant_type'] = $product->getVariantType();
        $result['selling_from'] = ($product->getSellingFrom() !== null) ? $product->getSellingFrom()->format('Y-m-d') : date('Y-m-d');
        $result['parameters'] = $this->extractParametersForProductIncludingVariants($result['parameters'], $variants, $locale, $result['variant_type']);
        $result['parameters_for_filter'] = $this->extractParametersForFilterForProductIncludingVariants(
            $result['parameters'],
            $this->productFacade->getSellableVariantsForProduct($product, $domainId),
            $locale,
            $result['variant_type']
        );
        $result['main_variant_id'] = $product->isVariant() ? $product->getMainVariant()->getId() : null;
        $result['gifts'] = $this->productFacade->getProductGiftName($product, $domainId, $locale);
        $result['minimum_amount'] = $product->getRealMinimumAmount();
        $result['amount_multiplier'] = $product->getAmountMultiplier();
        $result['variants_aliases'] = $this->getVariantsAliases($variants, $locale);
        $result['variants_count'] = count($result['variants_aliases']);
        $result['set_items'] = $this->productSetFacade->getAllItemsDataByMainProduct($product, $locale);
        if ($product->isMainVariant()) {
            $result['catnum'] = array_merge([$result['catnum']], $this->getVariantsCatnums($variants));
        }
        $isInAnySaleStock = $product->isInAnySaleStock();
        $result['prices_for_filter'] = $this->getPricesForFilterIncludingVariants($product, $domainId, $result['prices'], $isInAnySaleStock);
        $result['delivery_days'] = $product->isMainVariant() ? '' : $product->getDeliveryDays();
        $result['is_available_in_days'] = $product->isMainVariant() ? false : $product->isAvailableInDays();
        $result['real_sale_stocks_quantity'] = $product->isSellingDenied() || $product->isMainVariant() ? 0 : $product->getRealSaleStocksQuantity();
        $result['is_in_any_sale_stock'] = $isInAnySaleStock;
        $result['pohoda_product_type'] = $this->getPohodaProductType($product);
        $result['ordering_priority'] = $product->getBiggestVariantOrderingPriority();
        $result['internal_stocks_quantity'] = $product->getBiggestVariantRealInternalStockQuantity();
        $result['external_stocks_quantity'] = $product->getBiggestVariantRealExternalStockQuantity();
        $result['parameters'] = $this->appendSetItemParameters($locale, $product, $result['parameters']);
        $result['parameters_for_filter'] = $this->appendSetItemParametersForFilter($locale, $product, $result['parameters_for_filter']);
        $result['warranty'] = $product->getWarranty();
        $result['recommended'] = $product->isRecommended();
        $result['supplier_set'] = $product->isSupplierSet();
        $result['main_category_path'] = $this->getMainCategoryPath($product, $domainId);
        $result['is_in_news'] = $product->isProductInNews($domainId);
        $result['availability'] = $this->availabilityFacade->getAvailabilityText($product, $locale);
        $result['availability_color'] = $product->getCalculatedAvailability()->getRgbColor();
        $result['boosting_name'] = $product->isGiftCertificate() ? $product->getName($locale) : '';
        $result['in_stock'] = $this->extractImmediateAvailability($product, $locale);
        $result['available'] = $this->extractAvailability($product, $locale);
        $result['product_news_active_from'] = ($product->productNewsFrom($domainId) !== null) ? $product->productNewsFrom($domainId)->format('Y-m-d') : null;

        return $result;
    }

    /**
     * @param int $domainId
     * @param \App\Model\Product\Product $product
     * @return array
     */
    protected function extractPrices(int $domainId, BaseProduct $product): array
    {
        $isVariant = $product->isVariant();
        $productId = $product->getId();
        if ($isVariant && isset($this->variantsCachedPrices[$domainId][$productId])) {
            return $this->variantsCachedPrices[$domainId][$productId];
        }
        $defaultPricingGroupOnDomain = $product->isInAnySaleStock() ? $this->pricingGroupFacade->getSalePricePricingGroup($domainId) : $this->pricingGroupFacade->getDefaultPricingGroup($domainId);
        $standardPricingGroupOnDomain = $product->isInAnySaleStock() ? $this->pricingGroupFacade->getDefaultPricingGroup($domainId) : $this->pricingGroupFacade->getStandardPricePricingGroup($domainId);

        $pricesArray = parent::extractPrices($domainId, $product);

        $defaultPricingGroupId = $defaultPricingGroupOnDomain->getId();
        $standardPricingGroupId = $standardPricingGroupOnDomain->getId();
        foreach ($pricesArray as $key => $priceArray) {
            $priceArray['is_sale'] = ($product->isInAnySaleStock());
            $priceArray['is_default'] = ($priceArray['pricing_group_id'] === $defaultPricingGroupId);
            $priceArray['is_standard'] = ($priceArray['pricing_group_id'] === $standardPricingGroupId);
            $pricesArray[$key] = $priceArray;
        }

        if ($isVariant && isset($this->variantsCachedPrices[$domainId][$productId]) === false) {
            $this->variantsCachedPrices[$domainId][$productId] = $pricesArray;
        }

        return $pricesArray;
    }

    /**
     * we don't want export main product's parameters if has variants,
     * because it caused bad counts (or zero) by parameter value. ex: balení: 100ml (0)
     *
     * @param array $baseParameters
     * @param \App\Model\Product\Product[] $variants
     * @param string $locale
     * @param string $variantType
     * @return array
     */
    private function extractParametersForProductIncludingVariants(
        array $baseParameters,
        array $variants,
        string $locale,
        string $variantType): array
    {
        if ($variantType === BaseProduct::VARIANT_TYPE_NONE || $variantType === BaseProduct::VARIANT_TYPE_VARIANT) {
            return $baseParameters;
        } else {
            $parameters = [];
            foreach ($variants as $variant) {
                $parameters = array_merge($this->extractParameters($locale, $variant), $parameters);
            }

            return array_values(array_unique($parameters, SORT_REGULAR));
        }
    }

    /**
     * @param array $baseParameters
     * @param \App\Model\Product\Product[] $variants
     * @param string $locale
     * @param string $variantType
     * @return array
     */
    private function extractParametersForFilterForProductIncludingVariants(
        array $baseParameters,
        array $variants,
        string $locale,
        string $variantType
    ): array {
        if ($variantType === BaseProduct::VARIANT_TYPE_NONE || $variantType === BaseProduct::VARIANT_TYPE_VARIANT) {
            if (!empty($baseParameters)) {
                return ['parameter_groups' => $baseParameters];
            }

            return [];
        } else {
            $parameters = [];

            foreach ($variants as $variant) {
                $parameters[] = ['parameter_groups' => $this->extractParameters($locale, $variant)];
            }

            return $parameters;
        }
    }

    /**
     * @param \App\Model\Product\Product[] $variants
     * @param string $locale
     * @return string[]
     */
    private function getVariantsAliases(array $variants, string $locale): array
    {
        $variantsAliases = [];
        foreach ($variants as $variant) {
            $variantsAliases[] = $variant->getVariantAlias($locale);
        }

        return array_values(array_filter($variantsAliases));
    }

    /**
     * @param \App\Model\Product\Product[] $variants
     * @return string[]
     */
    private function getVariantsCatnums(array $variants): array
    {
        $variantsCatnums = [];
        foreach ($variants as $variant) {
            $variantsCatnums[] = $variant->getCatnum();
        }

        return array_filter($variantsCatnums);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @param array $prices
     * @param bool $isInAnySaleStock
     * @return array
     */
    private function getPricesForFilterIncludingVariants(BaseProduct $product, int $domainId, array $prices, bool $isInAnySaleStock): array
    {
        $pricesForFilter = [];
        if ($product->isMainVariant() === false) {
            $pricesForFilter = $this->getPricesForFilterFromPrices($prices, $isInAnySaleStock, $domainId);
        } else {
            foreach ($this->productFacade->getSellableVariantsForProduct($product, $domainId) as $variant) {
                $variantPrices = $this->extractPrices($domainId, $variant);
                $variantPriceForFilter = $this->getPricesForFilterFromPrices($variantPrices, $isInAnySaleStock, $domainId);
                $pricesForFilter = array_merge($pricesForFilter, $variantPriceForFilter);
            }
        }

        return $pricesForFilter;
    }

    /**
     * @param array $prices
     * @param bool $isInAnySaleStock
     * @param mixed $domainId
     * @return array
     */
    private function getPricesForFilterFromPrices(array $prices, bool $isInAnySaleStock, $domainId): array
    {
        $pricesForFilter = [];
        $salePrice = $this->getSalePriceFromPriceArray($prices, $domainId);
        foreach ($prices as $price) {
            $pricesForFilter[] = [
                'pricing_group_id' => $price['pricing_group_id'],
                'price_with_vat' => $isInAnySaleStock ? $salePrice : $price['price_with_vat'],
            ];
        }

        return $pricesForFilter;
    }

    /**
     * @param array $prices
     * @param int $domainId
     * @return float
     */
    private function getSalePriceFromPriceArray(array $prices, int $domainId): float
    {
        $salePricePricingGroupId = $this->pricingGroupFacade->getSalePricePricingGroup($domainId)->getId();
        foreach ($prices as $price) {
            if ($price['pricing_group_id'] === $salePricePricingGroupId) {
                return $price['price_with_vat'];
            }
        }

        return 0.0;
    }

    /**
     * On FE, we do not want to display "clearance" flag at all, "sale" flag is used instead
     *
     * @param \App\Model\Product\Product $product
     * @return int[]
     */
    protected function extractFlags(BaseProduct $product): array
    {
        $saleFlag = $this->flagFacade->getSaleFlag();
        return array_values(array_unique(array_map(fn (Flag $flag) => $flag->isClearance() ? $saleFlag->getId() : $flag->getId(), $product->getActiveFlags())));
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return int
     */
    private function getPohodaProductType(Product $product): int
    {
        if ($product->isSupplierSet()) {
            return Product::POHODA_PRODUCT_TYPE_ID_PRODUCT_SET;
        }
        return $product->getPohodaProductType() ?? Product::POHODA_PRODUCT_TYPE_ID_SINGLE_PRODUCT;
    }

    /**
     * @param string $locale
     * @param \App\Model\Product\Product $product
     * @param array $parameters
     * @return array
     */
    private function appendSetItemParameters(string $locale, Product $product, array $parameters): array
    {
        foreach ($product->getProductSets() as $setItem) {
            foreach ($this->extractParameters($locale, $setItem->getItem()) as $parameter) {
                $parameters[] = $parameter;
            }
        }

        $uniqueParameters = [];

        foreach ($parameters as $parameter) {
            if (!in_array($parameter, $uniqueParameters, true)) {
                $uniqueParameters[] = $parameter;
            }
        }

        return $uniqueParameters;
    }

    /**
     * @param string $locale
     * @param \App\Model\Product\Product $product
     * @param array $parameters
     * @return array
     */
    private function appendSetItemParametersForFilter(string $locale, Product $product, array $parameters): array
    {
        $setItemParams = [];

        foreach ($product->getProductSets() as $setItem) {
            foreach ($this->extractParameters($locale, $setItem->getItem()) as $parameter) {
                if (!in_array($parameter, $setItemParams, true)) {
                    $setItemParams[] = $parameter;
                }
            }
        }

        if (!empty($setItemParams)) {
            $parameters[] = ['parameter_groups' => $setItemParams];
        }

        return $parameters;
    }

    /**
     * @param string $locale
     * @param \App\Model\Product\Product $product
     * @return array
     */
    protected function extractParameters(string $locale, BaseProduct $product): array
    {
        $productId = $product->getId();
        if (isset($this->cachedParameters[$locale][$productId]) === false) {
            $this->cachedParameters[$locale][$productId] = parent::extractParameters($locale, $product);
        }

        return $this->cachedParameters[$locale][$productId];
    }

    /**
     * @inheritDoc
     */
    protected function createQueryBuilder(int $domainId): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p')
            ->join(ProductVisibility::class, 'prv', Join::WITH, 'prv.product = p.id')
            ->andWhere('prv.domainId = :domainId')
            ->andWhere('prv.visible = TRUE')
            ->andWhere('p.sellingDenied = FALSE')
            ->andWhere('p.supplierSet = FALSE OR p.realStockQuantity > 0')
            ->andWhere('p.pohodaProductType IS NULL OR p.pohodaProductType != :productTypeSet OR p.realStockQuantity > 0')
            ->groupBy('p.id')
            ->orderBy('p.id')
            ->setParameter('domainId', $domainId)
            ->setParameter('productTypeSet', Product::POHODA_PRODUCT_TYPE_ID_PRODUCT_SET);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @return string
     */
    private function getMainCategoryPath(Product $product, int $domainId): string
    {
       return $this->categoryFacade->getCategoryFullPath($product, $this->domain->getDomainConfigById($domainId), '/') ?? '';
    }

    /**
     * It is now possible to get products data only for exporting updated stock quantities
     * @param int $domainId
     * @param string $locale
     * @param int[] $productIds
     * @param string|null $scope
     * @return array
     */
    public function getProductsDataForIds(int $domainId, string $locale, array $productIds, ?string $scope = null): array
    {
        $queryBuilder = $this->createQueryBuilder($domainId)
            ->andWhere('p.id IN (:productIds)')
            ->setParameter('productIds', $productIds);

        $query = $queryBuilder->getQuery();

        $result = [];
        /** @var \App\Model\Product\Product $product */
        foreach ($query->getResult() as $product) {
            $result[$product->getId()] = $this->extractResult($product, $domainId, $locale, $scope);
        }

        return $result;
    }

    /**
     * Copy pasted from vendor, added $scope parameter to define what data should be exported
     *
     * @param int $domainId
     * @param string $locale
     * @param int $lastProcessedId
     * @param int $batchSize
     * @param string|null $scope
     * @return array
     */
    public function getProductsData(int $domainId, string $locale, int $lastProcessedId, int $batchSize, ?string $scope = null): array
    {
        $queryBuilder = $this->createQueryBuilder($domainId)
            ->andWhere('p.id > :lastProcessedId')
            ->setParameter('lastProcessedId', $lastProcessedId)
            ->setMaxResults($batchSize);

        $query = $queryBuilder->getQuery();

        $results = [];
        /** @var \App\Model\Product\Product $product */
        foreach ($query->getResult() as $product) {
            $results[$product->getId()] = $this->extractResult($product, $domainId, $locale, $scope);
        }

        return $results;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param string $locale
     * @return bool
     */
    private function extractImmediateAvailability(Product $product, string $locale): bool
    {
        if (!$product->isMainVariant()) {
            return $product->getCalculatedAvailability()->isImmediatelyAvailable();
        }

        foreach ($product->getVariants($locale) as $variant) {
            if ($variant->getCalculatedAvailability()->isImmediatelyAvailable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param string $locale
     * @return bool
     */
    private function extractAvailability(Product $product, string $locale): bool
    {
        if (!$product->isMainVariant()) {
            return $product->getCalculatedAvailability()->isAvailable();
        }

        foreach ($product->getVariants($locale) as $variant) {
            if ($variant->getCalculatedAvailability()->isAvailable()) {
                return true;
            }
        }

        return false;
    }
}
