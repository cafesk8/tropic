<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Model\Product\Availability\AvailabilityFacade;
use App\Model\Product\Flag\ProductFlagDataFactory;
use App\Model\Product\Parameter\ProductParameterValueDataFactory;
use App\Model\Product\Set\ProductSetFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Component\Plugin\PluginCrudExtensionFacade;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade;
use Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryRepository;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductInputPriceFacade;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\FrameworkBundle\Model\Product\ProductData as BaseProductData;
use Shopsys\FrameworkBundle\Model\Product\ProductDataFactory as BaseProductDataFactory;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository;
use Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade;

/**
 * @property \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade
 */
class ProductDataFactory extends BaseProductDataFactory
{
    private ProductSetFacade $productSetFacade;

    private ProductFlagDataFactory $productFlagDataFactory;

    private UploadedFileDataFactoryInterface $uploadedFileDataFactory;

    /**
     * @param \App\Model\Pricing\Vat\VatFacade $vatFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductInputPriceFacade $productInputPriceFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade $unitFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Product\ProductRepository $productRepository
     * @param \App\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \App\Model\Product\Accessory\ProductAccessoryRepository $productAccessoryRepository
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \Shopsys\FrameworkBundle\Component\Plugin\PluginCrudExtensionFacade $pluginDataFormExtensionFacade
     * @param \App\Model\Product\Parameter\ProductParameterValueDataFactory $productParameterValueDataFactory
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Product\Set\ProductSetFacade $productSetFacade
     * @param \App\Model\Product\Availability\AvailabilityFacade $availabilityFacade
     * @param \App\Model\Product\Flag\ProductFlagDataFactory $productFlagDataFactory
     * @param \Shopsys\FrameworkBundle\Component\UploadedFile\UploadedFileDataFactoryInterface $uploadedFileDataFactory
     */
    public function __construct(
        VatFacade $vatFacade,
        ProductInputPriceFacade $productInputPriceFacade,
        UnitFacade $unitFacade,
        Domain $domain,
        ProductRepository $productRepository,
        ParameterRepository $parameterRepository,
        FriendlyUrlFacade $friendlyUrlFacade,
        ProductAccessoryRepository $productAccessoryRepository,
        ImageFacade $imageFacade,
        PluginCrudExtensionFacade $pluginDataFormExtensionFacade,
        ProductParameterValueDataFactory $productParameterValueDataFactory,
        PricingGroupFacade $pricingGroupFacade,
        ProductSetFacade $productSetFacade,
        AvailabilityFacade $availabilityFacade,
        ProductFlagDataFactory $productFlagDataFactory,
        UploadedFileDataFactoryInterface $uploadedFileDataFactory
    ) {
        parent::__construct(
            $vatFacade,
            $productInputPriceFacade,
            $unitFacade,
            $domain,
            $productRepository,
            $parameterRepository,
            $friendlyUrlFacade,
            $productAccessoryRepository,
            $imageFacade,
            $pluginDataFormExtensionFacade,
            $productParameterValueDataFactory,
            $pricingGroupFacade,
            $availabilityFacade
        );
        $this->productSetFacade = $productSetFacade;
        $this->productFlagDataFactory = $productFlagDataFactory;
        $this->uploadedFileDataFactory = $uploadedFileDataFactory;
    }

    /**
     * @return \App\Model\Product\ProductData
     */
    public function create(): BaseProductData
    {
        $productData = new ProductData();
        $this->fillNew($productData);

        return $productData;
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return \App\Model\Product\ProductData
     */
    public function createFromProduct(BaseProduct $product): BaseProductData
    {
        $productData = new ProductData();
        $this->fillFromProduct($productData, $product);
        $productData->setNotNew();

        return $productData;
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     */
    public function fillNew(BaseProductData $productData)
    {
        parent::fillNew($productData);

        foreach ($this->domain->getAllIds() as $domainId) {
            $productData->generateToMergadoXmlFeeds[$domainId] = true;
            $productData->descriptionHashes[$domainId] = null;
            $productData->shortDescriptionHashes[$domainId] = null;
            $productData->shown[$domainId] = true;
            $productData->namesForMergadoFeed[$domainId] = null;
            $productData->transportFee[$domainId] = null;
        }

        $productData->stockQuantityByStoreId = [];
        $productData->youtubeVideoIds = [];
        $productData->usingStock = true;
        $productData->outOfStockAvailability = $this->availabilityFacade->getDefaultOutOfStockAvailability();
        $productData->outOfStockAction = Product::OUT_OF_STOCK_ACTION_SET_ALTERNATE_AVAILABILITY;
        $productData->files = $this->uploadedFileDataFactory->create();
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     * @param \App\Model\Product\Product $product
     */
    public function fillFromProduct(BaseProductData $productData, BaseProduct $product)
    {
        parent::fillFromProduct($productData, $product);

        if (!$product->isMainVariant()) {
            foreach ($product->getStoreStocks() as $storeStock) {
                $productData->stockQuantityByStoreId[$storeStock->getStore()->getId()] = $storeStock->getStockQuantity();
            }
        }

        $productData->pohodaId = $product->getPohodaId();
        $productData->pohodaProductType = $product->getPohodaProductType();
        $productData->mallExport = $product->isMallExport();
        $productData->mallExportedAt = $product->getMallExportedAt();
        $productData->updatedAt = $product->getUpdatedAt();
        $productData->baseName = $product->getBaseName();
        $productData->giftCertificate = $product->isGiftCertificate();
        $productData->minimumAmount = $product->getMinimumAmount();
        $productData->amountMultiplier = $product->getAmountMultiplier();
        $productData->youtubeVideoIds = $product->getYoutubeVideoIds();
        $productData->variantId = $product->getVariantId();
        $productData->registrationDiscountDisabled = $product->isRegistrationDiscountDisabled();
        $productData->promoDiscountDisabled = $product->isPromoDiscountDisabled();
        $productData->setItems = $this->getProductSets($product);
        $productData->deliveryDays = $product->getDeliveryDays();
        $productData->warranty = $product->getWarranty();
        $productData->flags = [];
        $productData->files = $this->uploadedFileDataFactory->createByEntity($product);

        foreach ($product->getProductFlags() as $productFlag) {
            $productData->flags[] = $this->productFlagDataFactory->createFromProductFlag($productFlag);
        }

        $productData->descriptionAutomaticallyTranslated = $product->isDescriptionAutomaticallyTranslated();
        $productData->shortDescriptionAutomaticallyTranslated = $product->isShortDescriptionAutomaticallyTranslated();
        $productData->bulky = $product->isBulky();
        $productData->oversized = $product->isOversized();
        $productData->stickers->orderedImages = $this->imageFacade->getImagesByEntityIndexedById($product, Product::IMAGE_TYPE_STICKER);
        $productData->supplierSet = $product->isSupplierSet();
        $productData->updatedByPohodaAt = $product->getUpdatedByPohodaAt();
        $productData->foreignSupplier = $product->isForeignSupplier();
        $productData->weight = $product->getWeight();
        $productData->transportFeeMultiplier = $product->getTransportFeeMultiplier();

        foreach ($this->domain->getAllIds() as $domainId) {
            $productData->generateToMergadoXmlFeeds[$domainId] = $product->isGenerateToMergadoXmlFeed($domainId);
            $productData->descriptionHashes[$domainId] = $product->getDescriptionHash($domainId);
            $productData->shortDescriptionHashes[$domainId] = $product->getShortDescriptionHash($domainId);
            $productData->shown[$domainId] = $product->isShownOnDomain($domainId);
            $productData->namesForMergadoFeed[$domainId] = $product->getNameForMergadoFeed($domainId);
            $productData->transportFee[$domainId] = $product->getTransportFee($domainId);
        }
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return array
     */
    protected function getProductSets(Product $product): array
    {
        $productSets = [];
        foreach ($this->productSetFacade->getAllByMainProduct($product) as $setItem) {
            $productSets[] = [
                'item' => $setItem->getItem(),
                'item_count' => $setItem->getItemCount(),
            ];
        }

        return $productSets;
    }
}
