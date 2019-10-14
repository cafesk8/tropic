<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Mall;

use MPAPI\Entity\Products\AbstractArticleEntity;
use MPAPI\Entity\Products\Product as MallProduct;
use MPAPI\Entity\Products\Variant;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Component\Mall\MallFacade;
use Shopsys\ShopBundle\Model\Category\CategoryFacade;
use Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade;
use Shopsys\ShopBundle\Model\Product\Mall\Exception\InvalidProductForMallExportException;
use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Shopsys\ShopBundle\Model\Store\StoreFacade;

class ProductMallExportMapper
{
    public const STOCK_QUANTITY_FUSE = 2;
    private const CZECH_LOCALE = DomainHelper::CZECH_LOCALE;
    private const CZECH_DOMAIN = DomainHelper::CZECH_DOMAIN;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser
     */
    private $productPriceCalculationForUser;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Image\ImageFacade
     */
    private $imageFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade
     */
    private $productCachedAttributesFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade
     */
    private $mainVariantGroupFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Store\StoreFacade
     */
    private $storeFacade;

    /**
     * @var \Shopsys\ShopBundle\Component\Mall\MallFacade
     */
    private $mallFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    private $pricingGroupSettingFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser $productPriceCalculationForUser
     * @param \Shopsys\FrameworkBundle\Component\Image\ImageFacade $imageFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade $mainVariantGroupFacade
     * @param \Shopsys\ShopBundle\Model\Category\CategoryFacade $categoryFacade
     * @param \Shopsys\ShopBundle\Model\Store\StoreFacade $storeFacade
     * @param \Shopsys\ShopBundle\Component\Mall\MallFacade $mallFacade
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     */
    public function __construct(
        ProductPriceCalculationForUser $productPriceCalculationForUser,
        ImageFacade $imageFacade,
        Domain $domain,
        ProductCachedAttributesFacade $productCachedAttributesFacade,
        ProductFacade $productFacade,
        MainVariantGroupFacade $mainVariantGroupFacade,
        CategoryFacade $categoryFacade,
        StoreFacade $storeFacade,
        MallFacade $mallFacade,
        PricingGroupSettingFacade $pricingGroupSettingFacade
    ) {
        $this->productPriceCalculationForUser = $productPriceCalculationForUser;
        $this->imageFacade = $imageFacade;
        $this->domain = $domain;
        $this->productCachedAttributesFacade = $productCachedAttributesFacade;
        $this->productFacade = $productFacade;
        $this->mainVariantGroupFacade = $mainVariantGroupFacade;
        $this->categoryFacade = $categoryFacade;
        $this->storeFacade = $storeFacade;
        $this->mallFacade = $mallFacade;
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \MPAPI\Entity\Products\Product|null
     */
    public function mapProductOrMainVariantGroup(Product $product): ?\MPAPI\Entity\Products\Product
    {
        if ($product->isMainVariant() === false) {
            return $this->mapProduct($product);
        } elseif ($product->isMainVariant() === true && $product->getMainVariantGroup() === null) {
            return $this->mapProduct($product);
        } elseif ($product->isMainVariant() === true && $product->getMainVariantGroup() !== null) {
            return $this->mapMainVariantGroupAsProduct($product);
        }

        return null;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \MPAPI\Entity\Products\Product
     */
    private function mapProduct(Product $product): MallProduct
    {
        /** @var \MPAPI\Entity\Products\Product $mallProduct */
        $mallProduct = $this->mapBasicInformation($product, false);

        if ($product->isMainVariant() === true) {
            $distinguishingParameters = $this->getDistinguishingParametersForProduct($product);
            $mallProduct->setVariableParameters($distinguishingParameters);
            $variants = $this->productFacade->getVariantsForProductExportToMall($product);

            foreach ($variants as $variant) {
                $mallProduct->addVariant($this->mapVariant($variant));
            }
        }

        $shortDescription = $product->getShortDescription(self::CZECH_DOMAIN);
        if ($shortDescription === null) {
            throw new InvalidProductForMallExportException('Short description not set');
        }

        $mallProduct->setShortdesc(mb_substr($shortDescription, 0, 295));
        $mallProduct->setLongdesc($product->getDescription(self::CZECH_DOMAIN));

        return $mallProduct;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $variant
     * @return \MPAPI\Entity\Products\Variant
     */
    private function mapVariant(Product $variant): Variant
    {
        /** @var \MPAPI\Entity\Products\Variant $mallVariant */
        $mallVariant = $this->mapBasicInformation($variant, true);
        $shortDescription = $variant->getMainVariant()->getShortDescription(self::CZECH_DOMAIN);
        if ($shortDescription === null) {
            throw new InvalidProductForMallExportException('Short description not set');
        }
        $mallVariant->setShortdesc($shortDescription);
        $mallVariant->setLongdesc($variant->getMainVariant()->getDescription(self::CZECH_DOMAIN));

        return $mallVariant;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param bool $isVariant
     * @return \MPAPI\Entity\Products\AbstractArticleEntity
     */
    private function mapBasicInformation(Product $product, bool $isVariant): AbstractArticleEntity
    {
        if ($isVariant === false) {
            $mallProduct = new MallProduct();
            $mallProduct->setVat($product->getVat()->getPercent());
            $mallProduct->setBrandId('BUSHMAN');

            $mallCategoryId = $this->categoryFacade->findMallCategoryForProduct($product, self::CZECH_DOMAIN);
            if ($mallCategoryId !== null) {
                $mallProduct->setCategoryId($mallCategoryId);
            }
        } else {
            $mallProduct = new Variant();
            $mallCategoryId = $this->categoryFacade->findMallCategoryForProduct($product->getMainVariant(), self::CZECH_DOMAIN);
        }

        $domainConfig = $this->domain->getDomainConfigById(self::CZECH_DOMAIN);
        $mallProduct->setId($product->getId());
        $mallProduct->setTitle($product->getName(self::CZECH_LOCALE));
        $this->setParameters($mallProduct, $product, $mallCategoryId);
        $mallProduct->setStatus(MallProduct::STATUS_ACTIVE);
        if ($product->isUsingStock()) {
            $stockQuantity = $this->findStockQuantity($product);
            $mallProduct->setInStock($stockQuantity);
        } else {
            throw new InvalidProductForMallExportException('Product not using stock');
        }

        // Minimum priority in Mall has to be 1
        if ($product->getOrderingPriority() === 0) {
            $mallProduct->setPriority(1);
        } else {
            $mallProduct->setPriority($product->getOrderingPriority());
        }

        $firstInLoop = false;
        $images = $this->imageFacade->getImagesByEntityIndexedById($product, null);

        if (count($images) === 0) {
            throw new InvalidProductForMallExportException('No image');
        }

        foreach ($images as $image) {
            $mallProduct->addMedia($this->imageFacade->getImageUrl($domainConfig, $image, 'original'), $firstInLoop === false);
            $firstInLoop = true;
        }

        if ($product->isMainVariant() === false || $isVariant) {
            if ($product->getEan() !== null) {
                $mallProduct->setBarcode((int)$product->getEan());
            }

            /** @var \Shopsys\ShopBundle\Model\Product\Pricing\ProductPrice $productPrice */
            $productPrice = $this->productPriceCalculationForUser->calculatePriceForUserAndDomainId($product, self::CZECH_DOMAIN);
            if ($productPrice->isActionPrice()) {
                $mallProduct->setPurchasePrice((float)$productPrice->defaultProductPrice()->getPriceWithVat()->getAmount());
                $mallProduct->setPrice((float)$productPrice->getPriceWithVat()->getAmount());
            } else {
                $mallProduct->setPurchasePrice((float)$productPrice->getPriceWithVat()->getAmount());
                $mallProduct->setPrice((float)$productPrice->getPriceWithVat()->getAmount());
            }
        }

        return $mallProduct;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \MPAPI\Entity\Products\Product|null
     */
    private function mapMainVariantGroupAsProduct(Product $product): ?MallProduct
    {
        /** @var \MPAPI\Entity\Products\Product $mallProduct */
        $mallProduct = $this->mapBasicInformation($product, false);
        $mallProduct->setId('group-' . $product->getMainVariantGroup()->getId());

        $shortDescription = $product->getShortDescription(self::CZECH_DOMAIN);
        if ($shortDescription === null) {
            throw new InvalidProductForMallExportException('Short description not set');
        }
        $mallProduct->setShortdesc($shortDescription);
        $mallProduct->setLongdesc($product->getDescription(self::CZECH_DOMAIN));

        $distinguishingParameters = $this->getDistinguishingParametersForProduct($product);
        $mallProduct->setVariableParameters($distinguishingParameters);
        $variants = $this->productFacade->getVariantsForMainVariantGroup($product->getMainVariantGroup(), self::CZECH_DOMAIN);

        if (count($variants) <= 0) {
            $variants = $this->mainVariantGroupFacade->getProductsForMainVariantGroupByProductAndDomainIdAndPricingGroup(
                $product,
                DomainHelper::CZECH_DOMAIN,
                $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId(DomainHelper::CZECH_DOMAIN)
            );
            if (count($variants) <= 0) {
                return null;
            }
        }

        foreach ($variants as $variant) {
            if ($variant->getEan() !== null) {
                $mallProduct->addVariant($this->mapVariant($variant));
            }
        }

        return $mallProduct;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return string[]
     */
    private function getDistinguishingParametersForProduct(Product $product): array
    {
        $distinguishingParameters = [];
        if ($product->getMainVariantGroup() !== null && $product->getMainVariantGroup()->getDistinguishingParameter() !== null) {
            $distinguishingParameters[] = $product->getMainVariantGroup()->getDistinguishingParameter()->getMallId();
        }

        if ($product->getDistinguishingParameter() !== null) {
            $distinguishingParameters[] = $product->getDistinguishingParameter()->getMallId();
        }

        return $distinguishingParameters;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return int|null
     */
    private function findStockQuantity(Product $product): int
    {
        $defaultStore = $this->storeFacade->findCentralStore();

        if ($defaultStore === null && $product->isMainVariant() === true) {
            return $product->getTotalStockQuantityOfProductVariantsForMall();
        }

        if ($defaultStore === null && $product->getStockQuantity() !== null) {
            $stockQuantityForExport = $product->getStockQuantity() - self::STOCK_QUANTITY_FUSE;
            return $stockQuantityForExport > 0 ? $stockQuantityForExport : 0;
        }

        if ($defaultStore === null && $product->getStockQuantity() === null) {
            return 0;
        }

        foreach ($product->getStoreStocks() as $productStoreStocks) {
            if ($productStoreStocks->getStore()->getId() === $defaultStore->getId() && $productStoreStocks->getStockQuantity() !== null) {
                $stockQuantityForExport = $productStoreStocks->getStockQuantity() - self::STOCK_QUANTITY_FUSE;
                return $stockQuantityForExport > 0 ? $stockQuantityForExport : 0;
            }
        }

        return 0;
    }

    /**
     * @param \MPAPI\Entity\Products\AbstractArticleEntity $mallProduct
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @param string|null $categoryId
     */
    private function setParameters(AbstractArticleEntity $mallProduct, Product $product, ?string $categoryId): void
    {
        if ($categoryId === null) {
            return;
        }

        $mallParametersByCategoryId = $this->mallFacade->getParametersByCateogoryId($categoryId);

        $productParameters = $this->productCachedAttributesFacade->getProductParameterValues($product, self::CZECH_LOCALE);
        foreach ($productParameters as $productParameter) {
            /** @var \Shopsys\ShopBundle\Model\Product\Parameter\Parameter $parameter */
            $parameter = $productParameter->getParameter();
            $mallParameterId = $parameter->getMallId();

            if ($mallParameterId !== null && in_array($mallParameterId, $mallParametersByCategoryId, true) === true) {
                $mallProduct->setParameter($mallParameterId, $productParameter->getValue()->getText());
            }
        }
    }
}
