<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Model\Product\Group\ProductGroupFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Image\ImageFacade;
use Shopsys\FrameworkBundle\Component\Plugin\PluginCrudExtensionFacade;
use Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade;
use Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade;
use Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryRepository;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueDataFactoryInterface;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductInputPriceFacade;
use Shopsys\FrameworkBundle\Model\Product\Product as BaseProduct;
use Shopsys\FrameworkBundle\Model\Product\ProductData as BaseProductData;
use Shopsys\FrameworkBundle\Model\Product\ProductDataFactory as BaseProductDataFactory;
use Shopsys\FrameworkBundle\Model\Product\ProductRepository;
use Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade;

class ProductDataFactory extends BaseProductDataFactory
{
    /**
     * @var \App\Model\Product\Group\ProductGroupFacade
     */
    private $productGroupFacade;

    /**
     * @param \App\Model\Pricing\Vat\VatFacade $vatFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductInputPriceFacade $productInputPriceFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade $unitFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Product\ProductRepository $productRepository
     * @param \App\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \App\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryRepository $productAccessoryRepository
     * @param \App\Component\Image\ImageFacade $imageFacade
     * @param \Shopsys\FrameworkBundle\Component\Plugin\PluginCrudExtensionFacade $pluginDataFormExtensionFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueDataFactoryInterface $productParameterValueDataFactory
     * @param \App\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \App\Model\Product\Group\ProductGroupFacade $productGroupFacade
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
        ProductParameterValueDataFactoryInterface $productParameterValueDataFactory,
        PricingGroupFacade $pricingGroupFacade,
        ProductGroupFacade $productGroupFacade
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
            $pricingGroupFacade
        );
        $this->productGroupFacade = $productGroupFacade;
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
        }

        $productData->stockQuantityByStoreId = [];
        $productData->youtubeVideoIds = [];
    }

    /**
     * @param \App\Model\Product\ProductData $productData
     * @param \App\Model\Product\Product $product
     */
    public function fillFromProduct(BaseProductData $productData, BaseProduct $product)
    {
        parent::fillFromProduct($productData, $product);

        foreach ($product->getStoreStocks() as $storeStock) {
            $productData->stockQuantityByStoreId[$storeStock->getStore()->getId()] = $storeStock->getStockQuantity();
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
        $productData->eurCalculatedAutomatically = $product->isEurCalculatedAutomatically();
        $productData->promoDiscountDisabled = $product->isPromoDiscountDisabled();
        $productData->groupItems = $this->getProductGroups($product);

        foreach ($this->domain->getAllIds() as $domainId) {
            $productData->generateToMergadoXmlFeeds[$domainId] = $product->isGenerateToMergadoXmlFeed($domainId);
        }
    }

    /**
     * @param \App\Model\Product\Product $product
     * @return array
     */
    protected function getProductGroups(Product $product): array
    {
        $productGroups = [];
        foreach ($this->productGroupFacade->getAllByMainProduct($product) as $groupItem) {
            $productGroups[] = [
                'item' => $groupItem->getItem(),
                'item_count' => $groupItem->getItemCount(),
            ];
        }

        return $productGroups;
    }
}
