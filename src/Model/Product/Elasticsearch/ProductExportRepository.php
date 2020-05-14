<?php

declare(strict_types=1);

namespace App\Model\Product\Elasticsearch;

use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Product\Group\ProductGroupFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlRepository;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;
use Shopsys\FrameworkBundle\Model\Product\Elasticsearch\ProductExportRepository as BaseProductExportRepository;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityRepository;

/**
 * @property \App\Model\Product\Parameter\ParameterRepository $parameterRepository
 * @property \App\Component\Router\FriendlyUrl\FriendlyUrlRepository $friendlyUrlRepository
 * @property \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
 * @method string extractDetailUrl(int $domainId, \App\Model\Product\Product $product)
 * @method int[] extractFlags(\App\Model\Product\Product $product)
 * @method array extractParameters(string $locale, \App\Model\Product\Product $product)
 * @method array extractVisibility(int $domainId, \App\Model\Product\Product $product)
 * @method int[] extractVariantIds(\App\Model\Product\Product $product)
 */
class ProductExportRepository extends BaseProductExportRepository
{
    /**
     * @var \App\Model\Product\ProductFacade
     */
    protected $productFacade;

    /**
     * @var \App\Model\Pricing\Group\PricingGroupFacade
     */
    private $pricingGroupFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    private $pricingGroupSettingFacade;

    /**
     * @var \App\Model\Product\Group\ProductGroupFacade
     */
    private $productGroupFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlRepository $friendlyUrlRepository
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityRepository $productVisibilityRepository
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     * @param \App\Model\Product\Group\ProductGroupFacade $productGroupFacade
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     */
    public function __construct(
        EntityManagerInterface $em,
        ParameterRepository $parameterRepository,
        ProductFacade $productFacade,
        FriendlyUrlRepository $friendlyUrlRepository,
        Domain $domain,
        ProductVisibilityRepository $productVisibilityRepository,
        FriendlyUrlFacade $friendlyUrlFacade,
        PricingGroupSettingFacade $pricingGroupSettingFacade,
        ProductGroupFacade $productGroupFacade,
        PricingGroupFacade $pricingGroupFacade
    ) {
        parent::__construct($em, $parameterRepository, $productFacade, $friendlyUrlRepository, $domain, $productVisibilityRepository, $friendlyUrlFacade);
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
        $this->productGroupFacade = $productGroupFacade;
        $this->pricingGroupFacade = $pricingGroupFacade;
    }

    /**
     * @param int $domainId
     * @param string $locale
     * @param int $startFrom
     * @param int $batchSize
     * @return array
     */
    public function getProductsData(int $domainId, string $locale, int $startFrom, int $batchSize): array
    {
        $queryBuilder = $this->createQueryBuilder($domainId)
            ->setFirstResult($startFrom)
            ->setMaxResults($batchSize);

        $query = $queryBuilder->getQuery();

        $products = $query->getResult();

        $result = [];
        /** @var \App\Model\Product\Product $product */
        foreach ($products as $product) {
            $result[$product->getId()] = $this->extractResult($product, $domainId, $locale);
        }

        return $result;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @param string $locale
     * @return array
     */
    protected function extractResult(Product $product, int $domainId, string $locale): array
    {
        $variants = $this->productFacade->getVisibleVariantsForProduct($product, $domainId);
        $result = parent::extractResult($product, $domainId, $locale);

        $result['selling_from'] = ($product->getSellingFrom() !== null) ? $product->getSellingFrom()->format('Y-m-d') : date('Y-m-d');
        $result['parameters'] = $this->extractParametersForProductIncludingVariants($result['parameters'], $variants, $locale);
        $result['main_variant_id'] = $product->isVariant() ? $product->getMainVariant()->getId() : null;
        $result['gifts'] = $this->productFacade->getProductGiftNames($product, $domainId, $locale);
        $result['minimum_amount'] = $product->getRealMinimumAmount();
        $result['amount_multiplier'] = $product->getAmountMultiplier();
        $result['variants_aliases'] = $this->getVariantsAliases($product, $locale, $domainId);
        $result['variants_count'] = count($result['variants_aliases']);
        $result['group_items'] = $this->productGroupFacade->getAllForElasticByMainProduct($product, $locale);
        if ($product->isMainVariant()) {
            $result['catnum'] = array_merge([$result['catnum']], $this->getVariantsCatnums($product, $domainId));
        }
        $result['prices_for_filter'] = $this->getPricesForFilterIncludingVariants($product, $domainId);
        $result['delivery_days'] = $product->getDeliveryDays();
        $result['is_available_in_days'] = $product->isAvailableInDays();
        $result['real_sale_stocks_quantity'] = $product->getRealSaleStocksQuantity();
        $result['is_in_any_sale_stock'] = $product->isInAnySaleStock();

        return $result;
    }

    /**
     * @param int $domainId
     * @param \App\Model\Product\Product $product
     * @return array
     */
    protected function extractPrices(int $domainId, Product $product): array
    {
        $defaultPricingGroupOnDomain = $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($domainId);
        $standardPricingGroupOnDomain = $this->pricingGroupFacade->getStandardPricePricingGroup($domainId);
        $pricesArray = parent::extractPrices($domainId, $product);

        $defaultPricingGroupId = $defaultPricingGroupOnDomain->getId();
        $standardPricingGroupId = $standardPricingGroupOnDomain->getId();
        foreach ($pricesArray as $key => $priceArray) {
            $priceArray['is_default'] = ($priceArray['pricing_group_id'] === $defaultPricingGroupId);
            $priceArray['is_standard'] = ($priceArray['pricing_group_id'] === $standardPricingGroupId);
            $pricesArray[$key] = $priceArray;
        }

        return $pricesArray;
    }

    /**
     * @param array $baseParameters
     * @param \App\Model\Product\Product[] $variants
     * @param string $locale
     * @return array
     */
    private function extractParametersForProductIncludingVariants(array $baseParameters, array $variants, string $locale): array
    {
        $parameters = [];
        foreach ($variants as $variant) {
            $parameters = array_merge($this->extractParameters($locale, $variant), $parameters);
        }

        $parameters = array_merge($baseParameters, $parameters);

        return array_values(array_unique($parameters, SORT_REGULAR));
    }

    /**
     * see https://github.com/shopsys/shopsys/pull/1719
     * @param int $domainId
     * @param \App\Model\Product\Product $product
     * @return int[]
     */
    protected function extractCategories(int $domainId, Product $product): array
    {
        $categoryIds = [];
        $categoriesIndexedByDomainId = $product->getCategoriesIndexedByDomainId();
        if (isset($categoriesIndexedByDomainId[$domainId])) {
            foreach ($categoriesIndexedByDomainId[$domainId] as $category) {
                $categoryIds[] = $category->getId();
            }
        }

        return $categoryIds;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param string $locale
     * @param int $domainId
     * @return string[]
     */
    private function getVariantsAliases(Product $product, string $locale, int $domainId): array
    {
        $variantsAliases = [];
        foreach ($this->productFacade->getVisibleVariantsForProduct($product, $domainId) as $variant) {
            $variantsAliases[] = $variant->getVariantAlias($locale);
        }

        return array_filter($variantsAliases);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @return string[]
     */
    private function getVariantsCatnums(Product $product, int $domainId): array
    {
        $variantsCatnums = [];
        foreach ($this->productFacade->getVisibleVariantsForProduct($product, $domainId) as $variant) {
            $variantsCatnums[] = $variant->getCatnum();
        }

        return array_filter($variantsCatnums);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @return array
     */
    private function getPricesForFilterIncludingVariants(Product $product, int $domainId): array
    {
        if ($product->isMainVariant() === false) {
            return $this->getPricesForFilter($product, $domainId);
        } else {
            $pricesForFilter = [];
            foreach ($this->productFacade->getSellableVariantsForProduct($product, $domainId) as $variant) {
                $variantPrices = $this->getPricesForFilter($variant, $domainId);
                $pricesForFilter = array_merge($pricesForFilter, $variantPrices);
            }

            return $pricesForFilter;
        }
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @return array
     */
    private function getPricesForFilter(Product $product, int $domainId): array
    {
        $pricesForFilter = [];
        $productSellingPrices = $this->productFacade->getAllProductSellingPricesByDomainId($product, $domainId);
        foreach ($productSellingPrices as $productSellingPrice) {
            $sellingPrice = $productSellingPrice->getSellingPrice();

            $pricesForFilter[] = [
                'pricing_group_id' => $productSellingPrice->getPricingGroup()->getId(),
                'price_with_vat' => (float)$sellingPrice->getPriceWithVat()->getAmount(),
            ];
        }

        return $pricesForFilter;
    }
}
