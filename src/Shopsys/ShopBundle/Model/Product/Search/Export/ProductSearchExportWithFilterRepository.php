<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Search\Export;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlRepository;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductFacade;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityRepository;
use Shopsys\FrameworkBundle\Model\Product\Search\Export\ProductSearchExportWithFilterRepository as BaseProductSearchExportWithFilterRepository;
use Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade;
use Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade;

class ProductSearchExportWithFilterRepository extends BaseProductSearchExportWithFilterRepository
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    protected $productFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade
     */
    private $mainVariantGroupFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Product[]
     */
    private $productsIndexedByPricingGroupIdAndMainVariantGroup;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Product[]
     */
    private $variantsIndexedByPricingGroupIdAndMainVariantId;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    private $pricingGroupSettingFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade
     */
    private $productCachedAttributesFacade;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlRepository $friendlyUrlRepository
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityRepository $productVisibilityRepository
     * @param \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade $mainVariantGroupFacade
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
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
        ProductCachedAttributesFacade $productCachedAttributesFacade
    ) {
        parent::__construct($em, $parameterRepository, $productFacade, $friendlyUrlRepository, $domain, $productVisibilityRepository, $friendlyUrlFacade);
        $this->mainVariantGroupFacade = $mainVariantGroupFacade;
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
        $this->productsIndexedByPricingGroupIdAndMainVariantGroup = [];
        $this->variantsIndexedByPricingGroupIdAndMainVariantId = [];
        $this->productCachedAttributesFacade = $productCachedAttributesFacade;
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
        /** @var \Shopsys\ShopBundle\Model\Product\Product $product */
        foreach ($products as $product) {
            $result[] = $this->extractResult($product, $domainId, $locale);
        }

        return $result;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
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

        return $result;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @param string $locale
     * @return array
     */
    private function getMainVariantGroupProductsData(Product $product, string $locale): array
    {
        $mainVariantGroupProductsData = [];
        foreach ($this->productsIndexedByPricingGroupIdAndMainVariantGroup as $pricingGroupId => $productsIndexedByMainVariantGroup) {
            if ($product->getMainVariantGroup() !== null && count($productsIndexedByMainVariantGroup) > 0 && in_array($product->getMainVariantGroup()->getId(), array_keys($productsIndexedByMainVariantGroup), true)) {
                foreach ($productsIndexedByMainVariantGroup[$product->getMainVariantGroup()->getId()] as $mainVariantGroupProduct) {
                    /** @var \Shopsys\ShopBundle\Model\Product\Product $mainVariantGroupProduct */
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
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $variants
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
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
     * @return array
     */
    protected function extractPrices(int $domainId, Product $product): array
    {
        $defaultPricingGroupOnDomain = $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($domainId);
        $pricesArray = parent::extractPrices($domainId, $product);

        foreach ($pricesArray as $key => $priceArray) {
            $priceArray['domain_id'] = $domainId;
            $priceArray['is_default'] = ($priceArray['pricing_group_id'] === $defaultPricingGroupOnDomain->getId());
            $pricesArray[$key] = $priceArray;
        }

        return $pricesArray;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Product $product
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
