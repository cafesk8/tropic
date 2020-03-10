<?php

declare(strict_types=1);

namespace App\Model\Product\Mall;

use App\Component\Domain\DomainHelper;
use App\Component\Mall\MallFacade;
use App\Model\Category\CategoryFacade;
use App\Model\Product\Mall\Exception\InvalidProductForMallExportException;
use App\Model\Product\Product;
use App\Model\Product\ProductCachedAttributesFacade;
use App\Model\Product\ProductFacade;
use App\Model\Store\StoreFacade;
use MPAPI\Entity\Products\AbstractArticleEntity;
use MPAPI\Entity\Products\Product as MallProduct;
use MPAPI\Entity\Products\Variant;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForCustomerUser;

class ProductMallExportMapper
{
    public const STOCK_QUANTITY_FUSE = 2;
    private const CZECH_LOCALE = DomainHelper::CZECH_LOCALE;
    private const CZECH_DOMAIN = DomainHelper::CZECH_DOMAIN;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForCustomerUser
     */
    private $productPriceCalculationForCustomerUser;

    /**
     * @var \App\Component\Image\ImageFacade
     */
    private $imageFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Model\Product\ProductCachedAttributesFacade
     */
    private $productCachedAttributesFacade;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \App\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @var \App\Model\Store\StoreFacade
     */
    private $storeFacade;

    /**
     * @var \App\Component\Mall\MallFacade
     */
    private $mallFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceCalculationForCustomerUser $productPriceCalculationForUser
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Product\ProductCachedAttributesFacade $productCachedAttributesFacade
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Model\Store\StoreFacade $storeFacade
     * @param \App\Component\Mall\MallFacade $mallFacade
     */
    public function __construct(
        ProductPriceCalculationForCustomerUser $productPriceCalculationForUser,
        ImageFacade $imageFacade,
        Domain $domain,
        ProductCachedAttributesFacade $productCachedAttributesFacade,
        ProductFacade $productFacade,
        CategoryFacade $categoryFacade,
        StoreFacade $storeFacade,
        MallFacade $mallFacade
    ) {
        $this->productPriceCalculationForCustomerUser = $productPriceCalculationForUser;
        $this->imageFacade = $imageFacade;
        $this->domain = $domain;
        $this->productCachedAttributesFacade = $productCachedAttributesFacade;
        $this->productFacade = $productFacade;
        $this->categoryFacade = $categoryFacade;
        $this->storeFacade = $storeFacade;
        $this->mallFacade = $mallFacade;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \MPAPI\Entity\Products\Product
     */
    public function mapProduct(Product $product): MallProduct
    {
        /** @var \MPAPI\Entity\Products\Product $mallProduct */
        $mallProduct = $this->mapBasicInformation($product, false);

        if ($product->isMainVariant() === true) {
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
     * @param \App\Model\Product\Product $product
     * @return \MPAPI\Entity\Products\Variant
     */
    private function mapVariant(Product $product): Variant
    {
        /** @var \MPAPI\Entity\Products\Variant $mallVariant */
        $mallVariant = $this->mapBasicInformation($product, true);
        $shortDescription = $product->getShortDescriptionConsideringVariant(self::CZECH_DOMAIN);
        if ($shortDescription === null) {
            throw new InvalidProductForMallExportException('Short description not set');
        }
        $mallVariant->setShortdesc($shortDescription);
        $mallVariant->setLongdesc($product->getDescriptionConsideringVariant(self::CZECH_DOMAIN));

        return $mallVariant;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param bool $isVariant
     * @return \MPAPI\Entity\Products\AbstractArticleEntity
     */
    private function mapBasicInformation(Product $product, bool $isVariant): AbstractArticleEntity
    {
        $mallCategoryId = $this->categoryFacade->findMallCategoryForProduct($product, self::CZECH_DOMAIN);
        if ($isVariant === false) {
            $mallProduct = new MallProduct();
            $mallProduct->setVat($product->getVatForDomain(self::CZECH_DOMAIN)->getPercent());
            $mallProduct->setBrandId('SHOPSYS');

            if ($mallCategoryId !== null) {
                $mallProduct->setCategoryId($mallCategoryId);
            }
        } else {
            $mallProduct = new Variant();
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
            $mallProduct->addMedia($this->imageFacade->getImageUrl($domainConfig, $image, 'original', null), $firstInLoop === false);
            $firstInLoop = true;
        }

        if ($product->isMainVariant() === false || $isVariant) {
            if ($product->getEan() !== null) {
                $mallProduct->setBarcode((int)$product->getEan());
            }

            /** @var \App\Model\Product\Pricing\ProductPrice $productPrice */
            $productPrice = $this->productPriceCalculationForCustomerUser->calculatePriceForCustomerUserAndDomainId($product, self::CZECH_DOMAIN);
            if ($productPrice->isActionPriceByUsedForPromoCode()) {
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
     * @param \App\Model\Product\Product $product
     * @return int
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
     * @param \App\Model\Product\Product $product
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
            /** @var \App\Model\Product\Parameter\Parameter $parameter */
            $parameter = $productParameter->getParameter();
            $mallParameterId = $parameter->getMallId();

            if ($mallParameterId !== null && in_array($mallParameterId, $mallParametersByCategoryId, true) === true) {
                $mallProduct->setParameter($mallParameterId, $productParameter->getValue()->getText());
            }
        }
    }
}
