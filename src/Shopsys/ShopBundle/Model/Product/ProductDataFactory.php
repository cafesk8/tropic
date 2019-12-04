<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product;

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
use Shopsys\ShopBundle\Model\Product\Brand\BrandFacade;

class ProductDataFactory extends BaseProductDataFactory
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\Brand\BrandFacade
     */
    private $brandFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Vat\VatFacade $vatFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductInputPriceFacade $productInputPriceFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Unit\UnitFacade $unitFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductRepository $productRepository
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterRepository $parameterRepository
     * @param \Shopsys\FrameworkBundle\Component\Router\FriendlyUrl\FriendlyUrlFacade $friendlyUrlFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Accessory\ProductAccessoryRepository $productAccessoryRepository
     * @param \Shopsys\FrameworkBundle\Component\Image\ImageFacade $imageFacade
     * @param \Shopsys\FrameworkBundle\Component\Plugin\PluginCrudExtensionFacade $pluginDataFormExtensionFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueDataFactoryInterface $productParameterValueDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupFacade $pricingGroupFacade
     * @param \Shopsys\ShopBundle\Model\Product\Brand\BrandFacade $brandFacade
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
        BrandFacade $brandFacade
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
        $this->brandFacade = $brandFacade;
    }

    /**
     * @return \Shopsys\ShopBundle\Model\Product\ProductData
     */
    public function create(): BaseProductData
    {
        $productData = new ProductData();
        $this->fillNew($productData);
        $productData->brand = $this->brandFacade->getMainBushmanBrand();

        return $productData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     * @return \Shopsys\ShopBundle\Model\Product\ProductData
     */
    public function createFromProduct(BaseProduct $product): BaseProductData
    {
        $productData = new ProductData();
        $this->fillFromProduct($productData, $product);

        return $productData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     */
    public function fillNew(BaseProductData $productData)
    {
        parent::fillNew($productData);
        $nullForAllDomains = $this->getNullForAllDomains();
        $productData->actionPrices = $nullForAllDomains;

        $productData->storeStocks = [];
        $productData->generateToHsSportXmlFeed = true;
        $productData->finished = false;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     * @param \Shopsys\ShopBundle\Model\Product\Product $product
     */
    public function fillFromProduct(BaseProductData $productData, BaseProduct $product)
    {
        parent::fillFromProduct($productData, $product);

        foreach ($product->getStoreStocks() as $storeStock) {
            $productData->stockQuantityByStoreId[$storeStock->getStore()->getId()] = $storeStock->getStockQuantity();
        }

        if ($product->getMainVariantGroup() !== null) {
            $productData->productsInGroup = $product->getMainVariantGroup()->getProducts();
            $productData->distinguishingParameterForMainVariantGroup = $product->getMainVariantGroup()->getDistinguishingParameter();
        }

        foreach ($this->domain->getAllIds() as $domainId) {
            $productData->actionPrices[$domainId] = $product->getActionPrice($domainId);
        }

        $productData->transferNumber = $product->getTransferNumber();
        $productData->distinguishingParameter = $product->getDistinguishingParameter();
        $productData->mainVariantGroup = $product->getMainVariantGroup();
        $productData->generateToHsSportXmlFeed = $product->isGenerateToHsSportXmlFeed();
        $productData->finished = $product->isFinished();
        $productData->youtubeVideoId = $product->getYoutubeVideoId();
        $productData->mallExport = $product->isMallExport();
        $productData->mallExportedAt = $product->getMallExportedAt();
        $productData->updatedAt = $product->getUpdatedAt();
        $productData->baseName = $product->getBaseName();
        $productData->productType = $product->getProductType();
    }
}
