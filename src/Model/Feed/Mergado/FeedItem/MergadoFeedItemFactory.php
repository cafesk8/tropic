<?php

declare(strict_types=1);

namespace App\Model\Feed\Mergado\FeedItem;

use App\Component\MergadoTransportType\MergadoTransportTypeFacade;
use App\Model\Category\CategoryFacade;
use App\Model\Payment\PaymentFacade;
use App\Model\Product\Pricing\ProductPriceCalculation;
use App\Model\Transport\Transport;
use App\Model\Transport\TransportFacade;
use Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig;
use Shopsys\FrameworkBundle\Component\Image\Exception\ImageNotFoundException;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\Currency;
use Shopsys\FrameworkBundle\Model\Pricing\Currency\CurrencyFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Price;
use Shopsys\FrameworkBundle\Model\Product\Collection\ProductParametersBatchLoader;
use Shopsys\FrameworkBundle\Model\Product\Collection\ProductUrlsBatchLoader;
use Shopsys\FrameworkBundle\Model\Product\Product;

class MergadoFeedItemFactory
{
    private const FIRST_YOUTUBE_VIDEO_ID_INDEX = 0;

    private const FIRST_ALTERNATIVE_YOUTUBE_VIDEO_ID_INDEX = 1;

    public const AVAILABILITY_DISPATCH_TIME_DAYS = 3;

    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    private $currencyFacade;

    /**
     * @var \App\Component\Image\ImageFacade
     */
    private $imageFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    private $pricingGroupSettingFacade;

    /**
     * @var \App\Model\Product\Pricing\ProductPriceCalculation
     */
    private $productPriceCalculation;

    /**
     * @var \App\Model\Product\Collection\ProductUrlsBatchLoader
     */
    private $productUrlsBatchLoader;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Collection\ProductParametersBatchLoader
     */
    private $productParametersBatchLoader;

    /**
     * @var \App\Model\Transport\TransportFacade
     */
    private $transportFacade;

    /**
     * @var \App\Model\Payment\PaymentFacade
     */
    private $paymentFacade;

    /**
     * @var \App\Component\MergadoTransportType\MergadoTransportTypeFacade
     */
    private $mergadoTransportTypeFacade;

    /**
     * @var \App\Model\Category\CategoryFacade
     */
    private $categoryFacade;

    /**
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     * @param \App\Model\Product\Pricing\ProductPriceCalculation $productPriceCalculation
     * @param \App\Model\Product\Collection\ProductUrlsBatchLoader $productUrlsBatchLoader
     * @param \Shopsys\FrameworkBundle\Model\Product\Collection\ProductParametersBatchLoader $productParametersBatchLoader
     * @param \App\Model\Transport\TransportFacade $transportFacade
     * @param \App\Model\Payment\PaymentFacade $paymentFacade
     * @param \App\Component\MergadoTransportType\MergadoTransportTypeFacade $mergadoTransportTypeFacade
     * @param \App\Model\Category\CategoryFacade $categoryFacade
     */
    public function __construct(
        CurrencyFacade $currencyFacade,
        ImageFacade $imageFacade,
        PricingGroupSettingFacade $pricingGroupSettingFacade,
        ProductPriceCalculation $productPriceCalculation,
        ProductUrlsBatchLoader $productUrlsBatchLoader,
        ProductParametersBatchLoader $productParametersBatchLoader,
        TransportFacade $transportFacade,
        PaymentFacade $paymentFacade,
        MergadoTransportTypeFacade $mergadoTransportTypeFacade,
        CategoryFacade $categoryFacade
    ) {
        $this->currencyFacade = $currencyFacade;
        $this->imageFacade = $imageFacade;
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
        $this->productPriceCalculation = $productPriceCalculation;
        $this->productUrlsBatchLoader = $productUrlsBatchLoader;
        $this->productParametersBatchLoader = $productParametersBatchLoader;
        $this->transportFacade = $transportFacade;
        $this->paymentFacade = $paymentFacade;
        $this->mergadoTransportTypeFacade = $mergadoTransportTypeFacade;
        $this->categoryFacade = $categoryFacade;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return \App\Model\Feed\Mergado\FeedItem\MergadoFeedItem
     */
    public function create(Product $product, DomainConfig $domainConfig): MergadoFeedItem
    {
        $productImages = $this->getAllImageUrlsByProduct($product, $domainConfig);
        $productVideos = $product->isVariant() ? $product->getMainVariant()->getYoutubeVideoIds() : $product->getYoutubeVideoIds();
        $currency = $this->currencyFacade->getDomainDefaultCurrencyByDomainId($domainConfig->getId());

        return new MergadoFeedItem(
            $product->getId(),
            $product->isVariant() ? $product->getMainVariant()->getId() : null,
            $product->getCatnum(),
            $product->getEan(),
            $this->productUrlsBatchLoader->getProductUrl($product, $domainConfig),
            $product->getName($domainConfig->getLocale()),
            $this->categoryFacade->getCategoryFullPath($product, $domainConfig, ' / '),
            $this->getShortDescription($product, $domainConfig->getId()),
            $this->getDescription($product, $domainConfig->getId()),
            $this->getBenefits($product, $domainConfig),
            $this->getBrandName($product),
            $this->getPrice($product, $domainConfig)->getPriceWithoutVat()->getAmount(),
            $this->getPrice($product, $domainConfig)->getPriceWithVat()->getAmount(),
            $currency->getCode(),
            $this->getProductAvailability($product),
            $product->isUsingStock() && $product->getStockQuantity() > 0 ? 0 : (int)$product->getCalculatedAvailability()->getDispatchTime(),
            $this->productUrlsBatchLoader->getProductImageUrl($product, $domainConfig),
            $productImages,
            $productVideos[self::FIRST_YOUTUBE_VIDEO_ID_INDEX] ?? null,
            array_slice($productVideos, self::FIRST_ALTERNATIVE_YOUTUBE_VIDEO_ID_INDEX),
            $this->productParametersBatchLoader->getProductParametersByName($product, $domainConfig),
            $this->getMergadoTransports($currency, $domainConfig)
        );
    }

    /**
     * @param \App\Model\Product\Product $product
     * @param \Shopsys\FrameworkBundle\Component\Domain\Config\DomainConfig $domainConfig
     * @return array
     */
    private function getBenefits(Product $product, DomainConfig $domainConfig): array
    {
        /** @var \App\Model\Product\Product[] $productGifts */
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
     * @return \App\Model\Feed\Mergado\FeedItem\MergadoFeedDeliveryItem[]
     */
    private function getMergadoTransports(
        Currency $currency,
        DomainConfig $domainConfig
    ): array {
        $payments = $this->paymentFacade->getVisibleByDomainId($domainConfig->getId());
        $transports = $this->transportFacade->getVisibleByDomainId($domainConfig->getId(), $payments);
        $transportPrices = $this->transportFacade->getTransportPricesWithVatByCurrencyAndDomainIdIndexedByTransportId($currency, $domainConfig->getId());
        $mergadoTransports = [];

        foreach ($transports as $transport) {
            if ($this->mergadoTransportTypeFacade->isMergadoTransportTypeAllowed($transport->getMergadoTransportType())) {
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
        if ($product->isUsingStock() && $product->getStockQuantity() > 0) {
            return 'in stock';
        }

        if (!$product->isUsingStock() && $product->getCalculatedAvailability()->getDispatchTime() < self::AVAILABILITY_DISPATCH_TIME_DAYS) {
            return 'in stock';
        }

        return 'out of stock';
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
                foreach ($product->getVariants() as $variant) {
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
        return $this->productPriceCalculation->calculatePrice(
            $product,
            $domainConfig->getId(),
            $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($domainConfig->getId())
        );
    }
}
