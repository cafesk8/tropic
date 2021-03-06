<?php

declare(strict_types=1);

namespace App\Model\Feed\Mergado\FeedItem;

use App\Component\Image\ImageFacade;
use App\Component\MergadoTransportType\MergadoTransportTypeFacade;
use App\Model\Category\CategoryFacade;
use App\Model\Payment\PaymentFacade;
use App\Model\Pricing\Group\PricingGroupFacade;
use App\Model\Product\Pricing\ProductPrice;
use App\Model\Product\Pricing\ProductPriceCalculation;
use App\Model\Product\Product;
use App\Model\Transport\Transport;
use App\Model\Transport\TransportFacade;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\Image\Exception\ImageNotFoundException;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Collection\ProductParametersBatchLoader;
use Shopsys\FrameworkBundle\Model\Product\Collection\ProductUrlsBatchLoader;
use Shopsys\FrameworkBundle\Twig\PriceExtension;

class MergadoFeedItemFactory
{
    private const FIRST_YOUTUBE_VIDEO_ID_INDEX = 0;

    private const FIRST_ALTERNATIVE_YOUTUBE_VIDEO_ID_INDEX = 1;

    private CurrencyFacade $currencyFacade;

    private ImageFacade $imageFacade;

    private ProductPriceCalculation $productPriceCalculation;

    private ProductUrlsBatchLoader $productUrlsBatchLoader;

    private ProductParametersBatchLoader $productParametersBatchLoader;

    private TransportFacade $transportFacade;

    private PaymentFacade $paymentFacade;

    private MergadoTransportTypeFacade $mergadoTransportTypeFacade;

    private CategoryFacade $categoryFacade;

    private PricingGroupFacade $pricingGroupFacade;

    private PriceExtension $priceExtension;

    /**
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \App\Model\Product\Pricing\ProductPriceCalculation $productPriceCalculation
     * @param \App\Model\Product\Collection\ProductUrlsBatchLoader $productUrlsBatchLoader
     * @param \Shopsys\FrameworkBundle\Model\Product\Collection\ProductParametersBatchLoader $productParametersBatchLoader
     * @param \App\Model\Transport\TransportFacade $transportFacade
     * @param \App\Model\Payment\PaymentFacade $paymentFacade
     * @param \App\Component\MergadoTransportType\MergadoTransportTypeFacade $mergadoTransportTypeFacade
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \Shopsys\FrameworkBundle\Twig\PriceExtension $priceExtension
     */
    public function __construct(
        CurrencyFacade $currencyFacade,
        ImageFacade $imageFacade,
        ProductPriceCalculation $productPriceCalculation,
        ProductUrlsBatchLoader $productUrlsBatchLoader,
        ProductParametersBatchLoader $productParametersBatchLoader,
        TransportFacade $transportFacade,
        PaymentFacade $paymentFacade,
        MergadoTransportTypeFacade $mergadoTransportTypeFacade,
        CategoryFacade $categoryFacade,
        PricingGroupFacade $pricingGroupFacade,
        PriceExtension $priceExtension
    ) {
        $this->currencyFacade = $currencyFacade;
        $this->imageFacade = $imageFacade;
        $this->productPriceCalculation = $productPriceCalculation;
        $this->productUrlsBatchLoader = $productUrlsBatchLoader;
        $this->productParametersBatchLoader = $productParametersBatchLoader;
        $this->transportFacade = $transportFacade;
        $this->paymentFacade = $paymentFacade;
        $this->mergadoTransportTypeFacade = $mergadoTransportTypeFacade;
        $this->categoryFacade = $categoryFacade;
        $this->pricingGroupFacade = $pricingGroupFacade;
        $this->priceExtension = $priceExtension;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \App\Model\Feed\Mergado\FeedItem\MergadoFeedItem
     */
    public function create(Product $product, DomainConfig $domainConfig): MergadoFeedItem
    {
        $productImages = $this->getAllImageUrlsByProduct($product, $domainConfig);
        if (count($productImages) === 0 && $product->isVariant()) {
            $productImages = $this->getAllImageUrlsByProduct($product->getMainVariant(), $domainConfig);
        }
        $productVideos = $product->isVariant() ? $product->getMainVariant()->getYoutubeVideoIds() : $product->getYoutubeVideoIds();
        $domainId = $domainConfig->getId();
        $currency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainId);
        $sellingPrice = $this->getPrice($product, $domainConfig);
        $mainImageUrl = null;
        if (count($productImages) > 0) {
            $mainImageUrl = reset($productImages);
        }
        return new MergadoFeedItem(
            $product->getId(),
            $product->isVariant() ? $product->getMainVariant()->getId() : null,
            $product->getCatnum(),
            $product->getEan(),
            $this->productUrlsBatchLoader->getProductUrl($product, $domainConfig),
            $this->getNameExact($product, $domainConfig),
            $this->categoryFacade->getCategoryFullPath($product, $domainConfig, ' / '),
            $this->getShortDescription($product, $domainId),
            $this->getDescription($product, $domainId),
            $this->getBenefits($product, $domainConfig),
            $this->getBrandName($product),
            $sellingPrice->getPriceWithoutVat()->getAmount(),
            $sellingPrice->getPriceWithVat()->getAmount(),
            $currency->getCode(),
            $this->getProductAvailability($product),
            $this->getProductDeliveryDays($product),
            $mainImageUrl,
            $productImages,
            $productVideos[self::FIRST_YOUTUBE_VIDEO_ID_INDEX] ?? null,
            array_slice($productVideos, self::FIRST_ALTERNATIVE_YOUTUBE_VIDEO_ID_INDEX),
            $this->productParametersBatchLoader->getProductParametersByName($product, $domainConfig),
            $this->getMergadoTransports($currency, $domainConfig, $product),
            $product->getWarranty(),
            $this->getPurchaseVsSellingPriceDifference($product, $sellingPrice, $domainId),
            $this->getSaleExclusionType($product, $domainId),
            $this->getStandardPrice($product, $domainId, $currency->getId(), $domainConfig->getLocale(), $sellingPrice->getStandardPrice()),
            (int)$product->isPromoDiscountDisabled($domainId)
        );
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return array
     */
    private function getBenefits(Product $product, DomainConfig $domainConfig): array
    {
        $productGifts = $product->getGifts($domainConfig->getId());

        $benefits = [];
        foreach ($productGifts as $productGift) {
            $benefits[] = $productGift->getName($domainConfig->getLocale());
        }

        return $benefits;
    }

    /**
     * @param \App\Model\Pricing\Currency\Currency $currency
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Feed\Mergado\FeedItem\MergadoFeedDeliveryItem[]
     */
    private function getMergadoTransports(
        Currency $currency,
        DomainConfig $domainConfig,
        Product $product
    ): array {
        $payments = $this->paymentFacade->getVisibleByDomainId($domainConfig->getId());
        $transports = $this->transportFacade->getVisibleByDomainId($domainConfig->getId(), $payments);
        $transportPrices = $this->transportFacade->getTransportPricesWithVatByCurrencyAndDomainIdIndexedByTransportId($currency, $domainConfig->getId());
        $mergadoTransports = [];

        foreach ($transports as $transport) {
            if ($this->mergadoTransportTypeFacade->isMergadoTransportTypeAllowed($transport->getMergadoTransportType())) {
                if (($product->isBulky() && !$transport->isBulkyAllowed()) || ($product->isOversized() && !$transport->isOversizedAllowed())) {
                    continue;
                }

                $mergadoTransports[] = new MergadoFeedDeliveryItem(
                    $transport->getId(),
                    $transport->getMergadoTransportType(),
                    $transportPrices[$transport->getId()],
                    $this->getCashOnDeliveryPaymentPrice($transport, $currency, $domainConfig)
                );
            }
        }

        return $mergadoTransports;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return string
     */
    private function getProductAvailability(Product $product): string
    {
        if ($product->isAvailableInDays() || $product->isAvailable()) {
            return 'in stock';
        }

        return 'out of stock';
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return int
     */
    private function getProductDeliveryDays(Product $product): int
    {
        if ($product->isAvailableInDays()) {
            return (int)preg_replace('/-.*$/', '', $product->getDeliveryDays());
        }

        if ($product->isAvailable()) {
            return 0;
        }

        return -1;
    }

    /**
     * @param \App\Model\Transport\Transport $transport
     * @param \App\Model\Pricing\Currency\Currency $currency
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    private function getCashOnDeliveryPaymentPrice(
        Transport $transport,
        Currency $currency,
        DomainConfig $domainConfig
    ): ?Money {
        $paymentPrices = $this->paymentFacade->getPaymentPricesWithVatByCurrencyAndDomainIdIndexedByPaymentId($currency, $domainConfig->getId());
        foreach ($transport->getPayments() as $payment) {
            if ($payment->isCashOnDelivery()) {
                return $paymentPrices[$payment->getId()];
            }
        }

        return null;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @return string|null
     */
    protected function getShortDescription(Product $product, int $domainId): ?string
    {
        if ($product->isVariant()) {
            return $product->getMainVariant()->getShortDescription($domainId);
        }

        return $product->getShortDescription($domainId);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @return string|null
     */
    protected function getDescription(Product $product, int $domainId): ?string
    {
        if ($product->isVariant()) {
            return $product->getMainVariant()->getDescription($domainId);
        }

        return $product->getDescription($domainId);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return string|null
     */
    protected function getBrandName(Product $product): ?string
    {
        if ($product->isVariant()) {
            $brand = $product->getMainVariant()->getBrand();
        } else {
            $brand = $product->getBrand();
        }

        return $brand !== null ? $brand->getName() : null;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return string[]
     */
    protected function getAllImageUrlsByProduct(Product $product, DomainConfig $domainConfig): array
    {
        try {
            $imageUrls = $this->imageFacade->getAllImagesUrlsByEntity($product, $domainConfig);

            if ($product->isMainVariant() === true) {
                foreach ($product->getVariants($domainConfig->getLocale()) as $variant) {
                    $variantUrls = $this->imageFacade->getAllImagesUrlsByEntity($variant, $domainConfig);
                    foreach ($variantUrls as $variantUrl) {
                        $imageUrls[] = $variantUrl;
                    }
                }
            }

            return $imageUrls;
        } catch (ImageNotFoundException $imageNotFoundException) {
            return [];
        }
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \App\Model\Product\Pricing\ProductPrice
     */
    protected function getPrice(Product $product, DomainConfig $domainConfig): Price
    {
        $domainId = $domainConfig->getId();

        if ($product->isInAnySaleStock()) {
            $price = $this->productPriceCalculation->calculatePrice(
                $product,
                $domainId,
                $this->pricingGroupFacade->getSalePricePricingGroup($domainId)
            );

            if ($price->getPriceWithVat()->isPositive()) {
                return $price;
            }
        }

        return $this->productPriceCalculation->calculatePrice(
            $product,
            $domainId,
            $this->pricingGroupFacade->getDefaultPricingGroup($domainId)
        );
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \App\Model\Product\Pricing\ProductPrice $sellingPrice
     * @param int $domainId
     * @return string
     */
    private function getPurchaseVsSellingPriceDifference(Product $product, ProductPrice $sellingPrice, int $domainId): string
    {
        $purchasePrice = $this->productPriceCalculation->calculatePrice(
            $product,
            $domainId,
            $this->pricingGroupFacade->getPurchasePricePricingGroup($domainId)
        );

        $sellingPriceWithVat = $sellingPrice->getPriceWithVat();
        $purchasePriceWithVat = $purchasePrice->getPriceWithVat();

        return $sellingPriceWithVat->subtract($purchasePriceWithVat)->getAmount();
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @return int|null
     */
    private function getSaleExclusionType(Product $product, int $domainId): ?int
    {
        $registrationDiscountDisabled = $product->isRegistrationDiscountDisabled($domainId);
        $promoDiscountDisabled = $product->isPromoDiscountDisabled($domainId);
        if ($registrationDiscountDisabled && $promoDiscountDisabled) {
            return 3;
        }
        if ($registrationDiscountDisabled) {
            return 1;
        }
        if ($promoDiscountDisabled) {
            return 2;
        }

        return null;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param int $domainId
     * @param int $currencyId
     * @param string $locale
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Price|null $providedStandardPrice
     * @return string|null
     */
    private function getStandardPrice(Product $product, int $domainId, int $currencyId, string $locale, ?Price $providedStandardPrice): ?string
    {
        if ($providedStandardPrice !== null) {
            $standardPrice = $providedStandardPrice;
        } else {
            $standardPrice = $this->productPriceCalculation->calculatePrice(
                $product,
                $domainId,
                $this->pricingGroupFacade->getStandardPricePricingGroup($domainId)
            );
        }

        $standardPriceWithVat = $standardPrice->getPriceWithVat();

        if ($standardPriceWithVat->isZero()) {
            return null;
        }

        return $this->priceExtension->priceTextWithCurrencyByCurrencyIdAndLocaleFilter($standardPriceWithVat, $currencyId, $locale);
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return string|null
     */
    private function getNameExact(Product $product, DomainConfig $domainConfig): ?string
    {
        $nameForMergadoFeed = $product->getNameForMergadoFeed($domainConfig->getId());
        $name = $product->getName($domainConfig->getLocale());

        return empty($nameForMergadoFeed) ? $name : $nameForMergadoFeed;
    }
}
