<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Mall;

use MPAPI\Entity\Products\AbstractArticleEntity;
use MPAPI\Entity\Products\Product as MallProduct;
use MPAPI\Entity\Products\Variant;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForUser;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductCachedAttributesFacade;
use Shopsys\ShopBundle\Model\Product\ProductFacade;

class ProductMallExportMapper
{
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

    public function __construct(
        ProductPriceCalculationForUser $productPriceCalculationForUser,
        ImageFacade $imageFacade,
        Domain $domain,
        ProductCachedAttributesFacade $productCachedAttributesFacade,
        ProductFacade $productFacade
    ) {
        $this->productPriceCalculationForUser = $productPriceCalculationForUser;
        $this->imageFacade = $imageFacade;
        $this->domain = $domain;
        $this->productCachedAttributesFacade = $productCachedAttributesFacade;
        $this->productFacade = $productFacade;
    }


    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \MPAPI\Entity\Products\AbstractArticleEntity|null
     */
    public function mapProductOrMainVariantGroup(Product $product): ?AbstractArticleEntity
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
        } else {
            $mallProduct = new Variant();
        }

        $domainConfig = $this->domain->getDomainConfigById(self::CZECH_DOMAIN);

        $mallProduct->setId($product->getId());
        $mallProduct->setTitle($product->getName(self::CZECH_LOCALE));
        $mallProduct->setShortdesc($product->getShortDescription(self::CZECH_DOMAIN));
        $mallProduct->setLongdesc($product->getDescription(self::CZECH_DOMAIN));
        $mallProduct->setPriority($product->getOrderingPriority());
        $mallProduct->setStatus(MallProduct::STATUS_ACTIVE);

        $productParameters = $this->productCachedAttributesFacade->getProductParameterValues($product, self::CZECH_LOCALE);
        foreach ($productParameters as $productParameter) {
            $mallProduct->setParameter($productParameter->getParameter()->getName(self::CZECH_LOCALE), $productParameter->getValue()->getText());
        }

        $firstInLoop = false;
        foreach ($this->imageFacade->getImagesByEntityIndexedById($product, null) as $image) {
            $mallProduct->addMedia($this->imageFacade->getImageUrl($domainConfig, $image, 'original'), $firstInLoop === false);
            $firstInLoop = true;
        }

        $productPrice = $this->productPriceCalculationForUser->calculatePriceForUserAndDomainId($product, self::CZECH_DOMAIN);

        if ($product->isMainVariant() === false || $isVariant) {
            $mallProduct->setBarcode($product->getEan());
            $mallProduct->setPrice($productPrice->getPriceWithVat()->getAmount());

            if ($product->isUsingStock()) {
                $mallProduct->setInStock($product->getStockQuantity());
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

        $distinguishingParameters = $this->getDistinguishingParametersForProduct($product);
        $mallProduct->setVariableParameters($distinguishingParameters);
        $variants = $this->productFacade->getVariantsForMainVariantGroup($product->getMainVariantGroup(), self::CZECH_DOMAIN);

        if (count($variants) <= 0) {
            return null;
        }

        foreach ($variants as $variant) {
            $mallProduct->addVariant($this->mapVariant($variant));
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
            $distinguishingParameters[] = $product->getMainVariantGroup()->getDistinguishingParameter()->getName(self::CZECH_LOCALE);
        }

        if ($product->getDistinguishingParameter() !== null) {
            $distinguishingParameters[] = $product->getDistinguishingParameter()->getName(self::CZECH_LOCALE);
        }

        return $distinguishingParameters;
    }
}
