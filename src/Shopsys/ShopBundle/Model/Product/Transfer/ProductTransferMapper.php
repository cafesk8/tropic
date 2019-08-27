<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Transfer;

use Shopsys\FrameworkBundle\Model\Product\Availability\AvailabilityFacade;
use Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter;
use Shopsys\FrameworkBundle\Model\Product\Parameter\ParameterValueDataFactory;
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
            $this->mapToNewProductData(
                $productData,
                $transferNumber,
                $productTransferResponseItemData,
                $productTransferResponseItemVariantData
            );
        } else {
            $productData = $this->productDataFactory->createFromProduct($product);
        }

        return $productData;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueData[] $productParameterValuesData
     * @param string|null $valueText
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueData[]
     */
    private function getSizeProductParameterValueDataByLocale(
        array $productParameterValuesData,
        ?string $valueText = null
): array {
        $missingLocales = $this->findMissingLocalesForProductParameterValue($this->parameterFacade->getSizeParameter(), $productParameterValuesData);

        $sizeProductParameterValueData = [];
        foreach ($missingLocales as $locale) {
            $parameterValueData = $this->parameterValueDataFactory->create();
            $parameterValueData->locale = $locale;
            $parameterValueData->text = $valueText;

            $productParameterValueData = $this->productParameterValueDataFactory->create();
            $productParameterValueData->parameter = $this->parameterFacade->getSizeParameter();
            $productParameterValueData->parameterValueData = $parameterValueData;
            $sizeProductParameterValueData[] = $productParameterValueData;
        }
        return $sizeProductParameterValueData;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueData[]
     * @param array $productParameterValuesData
     * @param string|null $valueText
     * @return \Shopsys\FrameworkBundle\Model\Product\Parameter\ProductParameterValueData[]
     */
    private function getColorProductParameterValueDataByLocale(
        array $productParameterValuesData,
        ?string $valueText = null
    ): array {
        $missingLocales = $this->findMissingLocalesForProductParameterValue($this->parameterFacade->getColorParameter(), $productParameterValuesData);

        $colorProductParameterValueData = [];
        foreach ($missingLocales as $locale) {
            $parameterValueData = $this->parameterValueDataFactory->create();
            $parameterValueData->locale = $locale;
            $parameterValueData->text = $valueText;

            $productParameterValueData = $this->productParameterValueDataFactory->create();
            $productParameterValueData->parameter = $this->parameterFacade->getColorParameter();
            $productParameterValueData->parameterValueData = $parameterValueData;

            $colorProductParameterValueData[] = $productParameterValueData;
        }

        return $colorProductParameterValueData;
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Product\Parameter\Parameter $parameter
     * @param array $productParameterValuesData
     * @return string[]
     */
    private function findMissingLocalesForProductParameterValue(Parameter $parameter, array $productParameterValuesData): array
    {
        $missingLocales = DomainHelper::LOCALES;

        foreach ($productParameterValuesData as $productParameterValueData) {
            $productParameter = $productParameterValueData->parameter;
            $locale = $productParameterValueData->parameterValueData->locale;

            if ($productParameter === $parameter && in_array($locale, $missingLocales, true)) {
                $indexToRemove = array_search($locale, $missingLocales, true);
                unset($missingLocales[$indexToRemove]);
            }
        }

        return $missingLocales;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\ProductData $productData
     * @param string $transferNumber
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferResponseItemData $productTransferResponseItemData
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferResponseItemVariantData|null $productTransferResponseItemVariantData
     */
    private function mapToNewProductData(
        ProductData $productData,
        string $transferNumber,
        ProductTransferResponseItemData $productTransferResponseItemData,
        ?ProductTransferResponseItemVariantData $productTransferResponseItemVariantData
    ): void {
        $productData->transferNumber = $transferNumber;
        $productData->name['cs'] = $productTransferResponseItemData->getName();
        $productData->descriptions[DomainHelper::CZECH_DOMAIN] = $productTransferResponseItemData->getDescription();
        $productData->availability = $this->availabilityFacade->getDefaultInStockAvailability();
        $productData->ean = $productTransferResponseItemVariantData->getEan();
        $productData->catnum = $productTransferResponseItemData->getTransferNumber();
        $productData->usingStock = true;
        $productData->stockQuantity = 0;
        if ($productTransferResponseItemVariantData->getColorName() !== null) {
            $productData->distinguishingParameterForMainVariantGroup = $this->parameterFacade->getColorParameter();
            $colorProductParameterValueData = $this->getColorProductParameterValueDataByLocale(
                $productData->parameters,
                $productTransferResponseItemVariantData->getColorName()
            );
            $productData->parameters = array_merge($productData->parameters, $colorProductParameterValueData);
        }

        if ($productTransferResponseItemVariantData->getSizeName() !== null) {
            $productData->distinguishingParameter = $this->parameterFacade->getSizeParameter();
            $sizeProductParameterValueData = $this->getSizeProductParameterValueDataByLocale(
                $productData->parameters,
                $productTransferResponseItemVariantData->getSizeName()
            );
            $productData->parameters = array_merge($productData->parameters, $sizeProductParameterValueData);
        }
    }
}
