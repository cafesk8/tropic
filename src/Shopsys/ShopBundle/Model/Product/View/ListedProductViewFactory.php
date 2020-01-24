<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\View;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice as BaseProductPrice;
use Shopsys\FrameworkBundle\Model\Product\Product;
use Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade;
use Shopsys\ReadModelBundle\Image\ImageView;
use Shopsys\ReadModelBundle\Product\Action\ProductActionView;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductView as BaseListedProductView;
use Shopsys\ReadModelBundle\Product\Listed\ListedProductViewFactory as BaseListedProductViewFactory;
use Shopsys\ShopBundle\Model\Product\Pricing\ProductPrice;

class ListedProductViewFactory extends BaseListedProductViewFactory
{
    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    private $pricingGroupSettingFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     */
    public function __construct(
        Domain $domain,
        ProductCachedAttributesFacade $productCachedAttributesFacade,
        PricingGroupSettingFacade $pricingGroupSettingFacade
    ) {
        parent::__construct($domain, $productCachedAttributesFacade);
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
    }

    /**
     * @param array $productArray
     * @param \Shopsys\ReadModelBundle\Image\ImageView|null $imageView
     * @param \Shopsys\ReadModelBundle\Product\Action\ProductActionView $productActionView
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \Shopsys\ShopBundle\Model\Product\View\MainVariantGroupProductView[] $mainVariantGroupProductViews
     * @return \Shopsys\ShopBundle\Model\Product\View\ListedProductView
     */
    public function createFromArray(
        array $productArray,
        ?ImageView $imageView,
        ProductActionView $productActionView,
        PricingGroup $pricingGroup,
        array $mainVariantGroupProductViews = []
    ): BaseListedProductView {
        $sellingPrice = $this->getSellingPrice(
            $productArray['prices'],
            $pricingGroup,
            $this->getMoney($productArray['action_price']),
            $this->getPriceFromPriceArray($productArray['default_price'])
        );
        $distinguishingParameterValues = $this->filterDistinguishingParameterValuesByPricingGroupId(
            $productArray['second_distinguishing_parameter_values'],
            $pricingGroup->getId()
        );

        return new ListedProductView(
            $productArray['id'],
            $productArray['name'],
            $productArray['short_description'],
            $productArray['availability'],
            $sellingPrice,
            $productArray['flags'],
            $productActionView,
            $imageView,
            $mainVariantGroupProductViews,
            $distinguishingParameterValues
        );
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param \Shopsys\ReadModelBundle\Image\ImageView|null $imageView
     * @param \Shopsys\ReadModelBundle\Product\Action\ProductActionView $productActionView
     * @param \Shopsys\ShopBundle\Model\Product\View\MainVariantGroupProductView[] $mainVariantGroupProductViews
     * @param \Shopsys\ShopBundle\Model\Product\Product[] $variantsIndexedByMainVariantId
     * @return \Shopsys\ShopBundle\Model\Product\View\ListedProductView
     */
    public function createFromProduct(
        Product $product,
        ?ImageView $imageView,
        ProductActionView $productActionView,
        array $mainVariantGroupProductViews = [],
        array $variantsIndexedByMainVariantId = []
    ): BaseListedProductView {
        $secondDistinguishingParameterValues = $this->getSecondDistinguishingParameterValues($product, $variantsIndexedByMainVariantId);

        return new ListedProductView(
            $product->getId(),
            $product->getName(),
            $product->getShortDescription($this->domain->getId()),
            $product->getCalculatedAvailability()->getName(),
            $this->productCachedAttributesFacade->getProductSellingPrice($product),
            $this->getFlagIdsForProduct($product),
            $productActionView,
            $imageView,
            $mainVariantGroupProductViews,
            $secondDistinguishingParameterValues
        );
    }

    /**
     * @param array $distinguishingParameterValues
     * @param int $pricingGroupId
     * @return array
     */
    private function filterDistinguishingParameterValuesByPricingGroupId(array $distinguishingParameterValues, int $pricingGroupId): array
    {
        $distinguishingParameterValuesForPricingGroup = [];
        foreach ($distinguishingParameterValues as $distinguishingParameterValue) {
            if ($distinguishingParameterValue['pricing_group_id'] === $pricingGroupId) {
                $distinguishingParameterValuesForPricingGroup[] = $distinguishingParameterValue['value'];
            }
        }

        return $distinguishingParameterValuesForPricingGroup;
    }

    /**
     * @param array $pricesArray
     * @param \Shopsys\ShopBundle\Model\Pricing\Group\PricingGroup $pricingGroup
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $actionPriceForCurrentDomain
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $defaultProductPrice
     * @return \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPrice|null
     */
    private function getSellingPrice(
        array $pricesArray,
        PricingGroup $pricingGroup,
        ?Money $actionPriceForCurrentDomain,
        ?Price $defaultProductPrice
    ): ?BaseProductPrice {
        $pricingGroupId = $pricingGroup->getId();
        foreach ($pricesArray as $priceArray) {
            if ($priceArray['pricing_group_id'] === $pricingGroupId) {
                $price = $this->getPriceFromPriceArray($priceArray);
                return new ProductPrice(
                    $price,
                    $priceArray['price_from'],
                    $pricingGroup,
                    $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($pricingGroup->getDomainId()),
                    $actionPriceForCurrentDomain,
                    $defaultProductPrice
                );
            }
        }

        return null;
    }

    /**
     * @param $priceArray
     * @return \Shopsys\FrameworkBundle\Model\Pricing\Price
     */
    private function getPriceFromPriceArray($priceArray): Price
    {
        $priceWithoutVat = Money::create((string)$priceArray['price_without_vat']);
        $priceWithVat = Money::create((string)$priceArray['price_with_vat']);

        return new Price($priceWithoutVat, $priceWithVat);
    }

    /**
     * @param float|null $amount
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    private function getMoney(?float $amount): ?Money
    {
        return $amount !== null ? Money::create((string)$amount) : null;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param array $variantsIndexedByMainVariantId
     * @return array
     */
    private function getSecondDistinguishingParameterValues(Product $product, array $variantsIndexedByMainVariantId): array
    {
        $secondDistinguishingParameterValues = [];
        if (isset($variantsIndexedByMainVariantId[$product->getId()])) {
            $distinguishingParameterValuesForProduct = $this->productCachedAttributesFacade->findDistinguishingParameterValuesForProducts($variantsIndexedByMainVariantId[$product->getId()]);
            foreach ($distinguishingParameterValuesForProduct as $mainVariantId => $variantIdsIndexedByParameterValues) {
                foreach ($variantIdsIndexedByParameterValues as $parameterValue => $variantId) {
                    $secondDistinguishingParameterValues[] = $parameterValue;
                }
            }
        }

        return $secondDistinguishingParameterValues;
    }
}
