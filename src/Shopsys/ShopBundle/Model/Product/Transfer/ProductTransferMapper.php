<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Transfer;

use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValueDataFactory;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueData;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueDataFactory;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade;
use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductData;
use Shopsys\ShopBundle\Model\Product\ProductDataFactory;

class ProductTransferMapper
{
    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueDataFactory
     */
    private $productParameterValueDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValueDataFactory
     */
    private $parameterValueDataFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductDataFactory
     */
    private $productDataFactory;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade
     */
    private $availabilityFacade;

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductDataFactory $productDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade $availabilityFacade
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade $parameterFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueDataFactory $productParameterValueDataFactory
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValueDataFactory $parameterValueDataFactory
     */
    public function __construct(ProductDataFactory $productDataFactory, AvailabilityFacade $availabilityFacade, ParameterFacade $parameterFacade, ProductParameterValueDataFactory $productParameterValueDataFactory, ParameterValueDataFactory $parameterValueDataFactory)
    {
        $this->productDataFactory = $productDataFactory;
        $this->availabilityFacade = $availabilityFacade;
        $this->parameterFacade = $parameterFacade;
        $this->productParameterValueDataFactory = $productParameterValueDataFactory;
        $this->parameterValueDataFactory = $parameterValueDataFactory;
    }

    /**
     * @param string $transferNumber
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferResponseItemData $productTransferResponseItemData
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferResponseItemVariantData|null $productTransferResponseItemVariantData
     * @param \Shopsys\ShopBundle\Model\Product\Product|null $product
     * @return \Shopsys\ShopBundle\Model\Product\ProductData
     */
    public function mapTransferDataToProductData(
        string $transferNumber,
        ProductTransferResponseItemData $productTransferResponseItemData,
        ?ProductTransferResponseItemVariantData $productTransferResponseItemVariantData,
        ?Product $product
    ): ProductData {
        if ($product === null) {
            $productData = $this->productDataFactory->create();
            $productData->transferNumber = $transferNumber;
        } else {
            $productData = $this->productDataFactory->createFromProduct($product);
        }

        $productData->name['cs'] = $productTransferResponseItemData->getName();
        $productData->descriptions[DomainHelper::CZECH_DOMAIN] = $productTransferResponseItemData->getDescription();
        $productData->availability = $this->availabilityFacade->getDefaultInStockAvailability();
        $productData->ean = $productTransferResponseItemVariantData->getEan();

        $productData->parameters = [];
        if ($productTransferResponseItemVariantData->getColorName() !== null) {
            $productData->distinguishingParameterForMainVariantGroup = $this->getColorParameter();
            if ($productTransferResponseItemVariantData->getColorName() !== null) {
                $productData->parameters[] = $this->getColorProductParameterValueData($productTransferResponseItemVariantData->getColorName());
            }
        }

        if ($productTransferResponseItemVariantData->getSizeName() !== null) {
            $productData->distinguishingParameter = $this->getSizeParameter();
            if ($productTransferResponseItemVariantData->getSizeName() !== null) {
                $productData->parameters[] = $this->getSizeProductParameterValueData($productTransferResponseItemVariantData->getSizeName());
            }
        }

        return $productData;
    }

    /**
     * @param string|null $valueText
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueData
     */
    private function getSizeProductParameterValueData(?string $valueText = null): ProductParameterValueData
    {
        $parameterValueData = $this->parameterValueDataFactory->create();
        $parameterValueData->locale = 'cs';
        $parameterValueData->text = $valueText;

        $productParameterValueData = $this->productParameterValueDataFactory->create();
        $productParameterValueData->parameter = $this->getSizeParameter();
        $productParameterValueData->parameterValueData = $parameterValueData;

        return $productParameterValueData;
    }

    /**
     * @param string|null $valueText
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueData
     */
    private function getColorProductParameterValueData(?string $valueText = null): ProductParameterValueData
    {
        $parameterValueData = $this->parameterValueDataFactory->create();
        $parameterValueData->locale = 'cs';
        $parameterValueData->text = $valueText;

        $productParameterValueData = $this->productParameterValueDataFactory->create();
        $productParameterValueData->parameter = $this->getColorParameter();
        $productParameterValueData->parameterValueData = $parameterValueData;

        return $productParameterValueData;
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter
     */
    public function getColorParameter(): Parameter
    {
        return $this->parameterFacade->findOrCreateParameterByNames([
            'cs' => 'Barva',
            'sk' => 'Farba',
            'de' => 'Farbe',
        ]);
    }

    /**
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter
     */
    private function getSizeParameter(): Parameter
    {
        return $this->parameterFacade->findOrCreateParameterByNames([
            'cs' => 'Velikost',
            'sk' => 'Velikosť',
            'de' => 'Größe',
        ]);
    }
}
