<?php

declare(strict_types=1);

namespace App\Model\Product\Elasticsearch;

use App\Model\Product\MainVariantGroup\MainVariantGroupFacade;
use App\Model\Product\Pricing\ProductManualInputPriceRepository;
use App\Model\Product\ProductCachedAttributesFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlRepository;
use Shopsys\FrameworkBundle\Model\Pricing\BasePriceCalculation;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;
use Shopsys\FrameworkBundle\Model\Pricing\PricingSetting;
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
 * @method int[] extractCategories(int $domainId, \App\Model\Product\Product $product)
 * @method array extractParameters(string $locale, \App\Model\Product\Product $product)
 * @method array extractVisibility(int $domainId, \App\Model\Product\Product $product)
 * @method array extractPrices(int $domainId, \App\Model\Product\Product $product)
 * @method int[] extractVariantIds(\App\Model\Product\Product $product)
 */
class ProductExportRepository extends BaseProductExportRepository
{
    /**
     * @var \App\Model\Product\ProductFacade
     */
    protected $productFacade;

    /**
     * @var \App\Model\Product\MainVariantGroup\MainVariantGroupFacade
     */
    private $mainVariantGroupFacade;

    /**
     * @var \App\Model\Product\Product[][][]
     */
    private $productsIndexedByPricingGroupIdAndMainVariantGroup;

    /**
     * @var \App\Model\Product\Product[][][]
     */
    private $variantsIndexedByPricingGroupIdAndMainVariantId;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    private $pricingGroupSettingFacade;

    /**
     * @var \App\Model\Product\ProductCachedAttributesFacade
     */
    private $productCachedAttributesFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\BasePriceCalculation
     */
    private $basePriceCalculation;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\PricingSetting
     */
    private $pricingSetting;

    /**
     * @var \App\Model\Product\Pricing\ProductManualInputPriceRepository
     */
    private $productManualInputPriceRepository;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlRepository $friendlyUrlRepository
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityRepository $productVisibilityRepository
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \App\Model\Product\MainVariantGroup\MainVariantGroupFacade $mainVariantGroupFacade
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     * @param \App\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     * @param \Shopsys\FrameworkBundle\Model\Pricing\BasePriceCalculation $basePriceCalculation
     * @param \Shopsys\FrameworkBundle\Model\Pricing\PricingSetting $pricingSetting
     * @param \App\Model\Product\Pricing\ProductManualInputPriceRepository $productManualInputPriceRepository
     */
    public function __construct(
        EntityManagerInterface $em,
        ParameterRepository $parameterRepository,
        ProductFacade $productFacade,
        FriendlyUrlRepository $friendlyUrlRepository,
        Domain $domain,
        ProductVisibilityRepository $productVisibilityRepository,
        FriendlyUrlFacade $friendlyUrlFacade,
        MainVariantGroupFacade $mainVariantGroupFacade,
        PricingGroupSettingFacade $pricingGroupSettingFacade,
        ProductCachedAttributesFacade $productCachedAttributesFacade,
        BasePriceCalculation $basePriceCalculation,
        PricingSetting $pricingSetting,
        ProductManualInputPriceRepository $productManualInputPriceRepository
    ) {
        parent::__construct($em, $parameterRepository, $productFacade, $friendlyUrlRepository, $domain, $productVisibilityRepository, $friendlyUrlFacade);
        $this->mainVariantGroupFacade = $mainVariantGroupFacade;
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
        $this->productsIndexedByPricingGroupIdAndMainVariantGroup = [];
        $this->variantsIndexedByPricingGroupIdAndMainVariantId = [];
        $this->productCachedAttributesFacade = $productCachedAttributesFacade;
        $this->basePriceCalculation = $basePriceCalculation;
        $this->pricingSetting = $pricingSetting;
        $this->productManualInputPriceRepository = $productManualInputPriceRepository;
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
        $this->productsIndexedByPricingGroupIdAndMainVariantGroup = $this->mainVariantGroupFacade->getProductsIndexedByPricingGroupIdAndMainVariantGroup($products, $domainId);
        $this->variantsIndexedByPricingGroupIdAndMainVariantId = $this->productFacade->getVariantsIndexedByPricingGroupIdAndMainVariantId($products, $domainId);

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
        $variants = $this->productFacade->getVariantsForProduct($product, $domainId);
        $result = parent::extractResult($product, $domainId, $locale);

        $result['selling_from'] = ($product->getSellingFrom() !== null) ? $product->getSellingFrom()->format('Y-m-d') : date('Y-m-d');
        $result['action_price'] = $product->getActionPrice($domainId) ? (float)$product->getActionPrice($domainId)->getAmount() : null;
        $result['parameters'] = $this->extractParametersForProductIncludingVariants($result['parameters'], $variants, $locale);
        $result['main_variant_group_products'] = $this->getMainVariantGroupProductsData($product, $locale);
        $result['second_distinguishing_parameter_values'] = $this->getSecondDistinguishingParameterValues($product, $locale);
        $result['main_variant_id'] = $product->isVariant() ? $product->getMainVariant()->getId() : null;
        $result['default_price'] = $this->getDefaultPriceArray($product, $domainId);

        return $result;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @return array
     */
    private function getDefaultPriceArray(Product $product, int $domainId): array
    {
        $defaultPriceWithVat = null;
        $defaultPriceWithoutVat = null;
        $defaultPricingGroupOnDomain = $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($domainId);
        $productManualInputPrices = $this->productManualInputPriceRepository->findByProductAndPricingGroupsForDomain($product, [$defaultPricingGroupOnDomain], $domainId);
        $manualInputPriceForDefaultPricingGroup = reset($productManualInputPrices);
        if ($manualInputPriceForDefaultPricingGroup !== false) {
            $defaultPrice = $this->basePriceCalculation->calculateBasePrice(
                Money::create($manualInputPriceForDefaultPricingGroup['inputPrice']),
                $this->pricingSetting->getInputPriceType(),
                $product->getVatForDomain($domainId)
            );
            $defaultPriceWithoutVat = (float)$defaultPrice->getPriceWithoutVat()->getAmount();
            $defaultPriceWithVat = (float)$defaultPrice->getPriceWithVat()->getAmount();
        }

        return [
            'price_with_vat' => $defaultPriceWithVat,
            'price_without_vat' => $defaultPriceWithoutVat,
        ];
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param string $locale
     * @return array
     */
    private function getMainVariantGroupProductsData(Product $product, string $locale): array
    {
        $mainVariantGroupProductsData = [];
        /** @var \App\Model\Product\Product[][] $productsIndexedByMainVariantGroup */
        foreach ($this->productsIndexedByPricingGroupIdAndMainVariantGroup as $pricingGroupId => $productsIndexedByMainVariantGroup) {
            if ($product->getMainVariantGroup() !== null && count($productsIndexedByMainVariantGroup) > 0 && in_array($product->getMainVariantGroup()->getId(), array_keys($productsIndexedByMainVariantGroup), true)) {
                foreach ($productsIndexedByMainVariantGroup[$product->getMainVariantGroup()->getId()] as $mainVariantGroupProduct) {
                    /** @var \App\Model\Product\Product $mainVariantGroupProduct */
                    $mainVariantGroupProductsData[] = [
                        'pricing_group_id' => $pricingGroupId,
                        'id' => $mainVariantGroupProduct->getId(),
                        'name' => $mainVariantGroupProduct->getName($locale),
                    ];
                }
            }
        }

        return $mainVariantGroupProductsData;
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
     * @param \App\Model\Product\Product $product
     * @param string $locale
     * @return array
     */
    private function getSecondDistinguishingParameterValues(Product $product, string $locale): array
    {
        $secondDistinguishingParameterValues = [];
        foreach ($this->variantsIndexedByPricingGroupIdAndMainVariantId as $pricingGroupId => $variantsIndexedByMainVariantId) {
            if (isset($variantsIndexedByMainVariantId[$product->getId()])) {
                $distinguishingParameterValuesForProduct = $this->productCachedAttributesFacade->findDistinguishingParameterValuesForProducts($variantsIndexedByMainVariantId[$product->getId()], $locale);
                foreach ($distinguishingParameterValuesForProduct as $mainVariantId => $variantIdsIndexedByParameterValues) {
                    foreach ($variantIdsIndexedByParameterValues as $parameterValue => $variantId) {
                        $secondDistinguishingParameterValues[] = [
                            'pricing_group_id' => $pricingGroupId,
                            'value' => $parameterValue,
                        ];
                    }
                }
            }
        }

        return $secondDistinguishingParameterValues;
    }
}
