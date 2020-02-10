<?php

declare(strict_types=1);

namespace App\Model\Product\Transfer;

use App\Component\Rest\RestClient;
use App\Component\Transfer\AbstractTransferImportCronModule;
use App\Component\Transfer\Response\TransferResponse;
use App\Component\Transfer\Response\TransferResponseItemDataInterface;
use App\Component\Transfer\TransferCronModuleDependency;
use App\Model\Product\MainVariantGroup\MainVariantGroup;
use App\Model\Product\MainVariantGroup\MainVariantGroupFacade;
use App\Model\Product\Parameter\ParameterFacade;
use App\Model\Product\Pricing\ProductPriceRecalculator;
use App\Model\Product\Product;
use App\Model\Product\ProductFacade;
use App\Model\Product\ProductVariantFacade;
use App\Model\Product\Transfer\Exception\InvalidProductTransferResponseItemDataException;
use Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculator;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade;

class ProductImportCronModule extends AbstractTransferImportCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_products';
    private const PRODUCT_NUMBER_TO_NOT_IMPORT = '899005';

    /**
     * @var \App\Component\Rest\RestClient
     */
    private $restClient;

    /**
     * @var \App\Model\Product\Transfer\ProductTransferMapper
     */
    private $productTransferMapper;

    /**
     * @var \App\Model\Product\Transfer\ProductTransferValidator
     */
    private $productTransferValidator;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade
     */
    private $productVisibilityFacade;

    /**
     * @var \App\Model\Product\ProductVariantFacade
     */
    private $productVariantFacade;

    /**
     * @var \App\Model\Product\MainVariantGroup\MainVariantGroupFacade
     */
    private $mainVariantGroupFacade;

    /**
     * @var array
     */
    private $productTree;

    /**
     * @var \App\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @var \App\Model\Product\Pricing\ProductPriceRecalculator
     */
    private $productPriceRecalculator;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculator
     */
    private $productAvailabilityRecalculator;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Component\Rest\RestClient $restClient
     * @param \App\Model\Product\Transfer\ProductTransferMapper $productTransferMapper
     * @param \App\Model\Product\Transfer\ProductTransferValidator $productTransferValidator
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade $productVisibilityFacade
     * @param \App\Model\Product\ProductVariantFacade $productVariantFacade
     * @param \App\Model\Product\MainVariantGroup\MainVariantGroupFacade $mainVariantGroupFacade
     * @param \App\Model\Product\Parameter\ParameterFacade $parameterFacade
     * @param \App\Model\Product\Pricing\ProductPriceRecalculator $productPriceRecalculator
     * @param \Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculator $productAvailabilityRecalculator
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        RestClient $restClient,
        ProductTransferMapper $productTransferMapper,
        ProductTransferValidator $productTransferValidator,
        ProductFacade $productFacade,
        ProductVisibilityFacade $productVisibilityFacade,
        ProductVariantFacade $productVariantFacade,
        MainVariantGroupFacade $mainVariantGroupFacade,
        ParameterFacade $parameterFacade,
        ProductPriceRecalculator $productPriceRecalculator,
        ProductAvailabilityRecalculator $productAvailabilityRecalculator
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->restClient = $restClient;
        $this->productTransferMapper = $productTransferMapper;
        $this->productTransferValidator = $productTransferValidator;
        $this->productFacade = $productFacade;
        $this->productVisibilityFacade = $productVisibilityFacade;
        $this->productVariantFacade = $productVariantFacade;
        $this->mainVariantGroupFacade = $mainVariantGroupFacade;
        $this->parameterFacade = $parameterFacade;
        $this->productPriceRecalculator = $productPriceRecalculator;
        $this->productAvailabilityRecalculator = $productAvailabilityRecalculator;
    }

    /**
     * @return string
     */
    protected function getTransferIdentifier(): string
    {
        return self::TRANSFER_IDENTIFIER;
    }

    /**
     * @return \App\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponse(): TransferResponse
    {
        $transfer = $this->transferFacade->getByIdentifier(self::TRANSFER_IDENTIFIER);

        if ($transfer->getLastStartAt() === null) {
            $restResponse = $this->restClient->get('/api/Eshop/Articles');
        } else {
            $restResponse = $this->restClient->get('/api/Eshop/ChangedArticles');
        }

        $transferDataItems = [];
        foreach ($restResponse->getData() as $restData) {
            if ($restData['Number'] === self::PRODUCT_NUMBER_TO_NOT_IMPORT) {
                continue;
            }
            $transferDataItems[] = new ProductTransferResponseItemData($restData);
        }

        return new TransferResponse($restResponse->getCode(), $transferDataItems);
    }

    /**
     * @param \App\Component\Transfer\Response\TransferResponseItemDataInterface $productTransferResponseItemData
     */
    protected function processTransferItemData(TransferResponseItemDataInterface $productTransferResponseItemData): void
    {
        if (!($productTransferResponseItemData instanceof ProductTransferResponseItemData)) {
            throw new InvalidProductTransferResponseItemDataException(
                sprintf('Invalid argument passed into method. Instance of `%s` was expected', ProductTransferResponseItemData::class)
            );
        }

        $this->productTransferValidator->validate($productTransferResponseItemData);

        $variantsCount = count($productTransferResponseItemData->getVariants());
        $this->productTree = [];

        if ($variantsCount > 0) {
            $lastProduct = null;
            foreach ($productTransferResponseItemData->getVariants() as $productVariantsItemData) {
                $lastProduct = $this->processProductItemWithVariants($productTransferResponseItemData, $productVariantsItemData);
            }
            if ($variantsCount === 1 && $lastProduct !== null) {
                $this->updateIfProductIsVariant($lastProduct);
            } else {
                $this->createProductTree();
            }
        } else {
            $this->processProductItemWithVariants($productTransferResponseItemData);
        }
        $this->logger->addInfo(sprintf('Products for group with transfer number `%s` were created', $productTransferResponseItemData->getTransferNumber()));

        $this->logger->addInfo('Recalculate products prices');
        $this->productPriceRecalculator->refreshAllPricingGroups();
        $this->productPriceRecalculator->runImmediateRecalculations();
        $this->logger->addInfo('Recalculate product availability');
        $this->productAvailabilityRecalculator->runImmediateRecalculations();
    }

    /**
     * @return bool
     */
    protected function isNextIterationNeeded(): bool
    {
        return false;
    }

    public function end(): void
    {
        $this->logger->addInfo('Recalculate products visibility');
        $this->productVisibilityFacade->refreshProductsVisibilityForMarked();
        parent::end();
    }

    /**
     * @param \App\Model\Product\Transfer\ProductTransferResponseItemData $productTransferResponseItemData
     * @param \App\Model\Product\Transfer\ProductTransferResponseItemVariantData|null $productTransferResponseItemVariantData
     * @return \App\Model\Product\Product
     */
    private function processProductItemWithVariants(
        ProductTransferResponseItemData $productTransferResponseItemData,
        ?ProductTransferResponseItemVariantData $productTransferResponseItemVariantData = null
    ): Product {
        $parameterColor = null;
        $parameterSize = null;

        $transferNumber = $productTransferResponseItemVariantData ? $productTransferResponseItemVariantData->getTransferNumber() : $productTransferResponseItemData->getTransferNumber();
        $product = $this->productFacade->findByTransferNumber($transferNumber);

        $productData = $this->productTransferMapper->mapTransferDataToProductData($transferNumber, $productTransferResponseItemData, $productTransferResponseItemVariantData, $product);

        if ($product === null) {
            $product = $this->productFacade->create($productData);
            $this->logger->addInfo(sprintf(
                'Product variant with transfer number `%s`(ID: `%s`) was created',
                $transferNumber,
                $product->getId()
            ));
        } else {
            $product = $this->productFacade->edit($product->getId(), $productData);
            $this->logger->addInfo(sprintf(
                'Product variant with transfer number `%s`(ID: `%s`) was edited',
                $transferNumber,
                $product->getId()
            ));
        }

        foreach ($productData->parameters as $parameter) {
            if ($parameter->parameter === $productData->distinguishingParameterForMainVariantGroup) {
                $parameterColor = $parameter->parameterValueData->text;
            }
        }

        $this->productTree[$parameterColor][] = $product;

        return $product;
    }

    private function createProductTree(): void
    {
        $mainVariants = [];
        $existingMainVariantGroup = null;
        foreach ($this->productTree as $colorValue => $secondParameterValuesWithProducts) {
            /** @var \App\Model\Product\Product|null $existingMainVariant */
            $existingMainVariant = null;
            $notVariants = [];
            foreach ($secondParameterValuesWithProducts as $productBySizeValue) {
                if ($productBySizeValue->isVariant() === true) {
                    if ($existingMainVariant === null) {
                        $existingMainVariant = $productBySizeValue->getMainVariant();
                    }

                    if ($existingMainVariantGroup === null) {
                        $existingMainVariantGroup = $this->findMainVariantGroup($existingMainVariant);
                    }
                } elseif ($productBySizeValue->isVariant() === false) {
                    $notVariants[] = $productBySizeValue;
                }
            }

            if ($existingMainVariant !== null) {
                $existingMainVariant->setDistinguishingParameter($this->parameterFacade->getSizeParameter());
                foreach ($notVariants as $notVariant) {
                    $existingMainVariant->addVariant($notVariant);
                }
                $existingMainVariant->updateCzechNamesWithColor((string)$colorValue);
                $this->productFacade->flushMainVariant($existingMainVariant);
                $mainVariants[] = $existingMainVariant;
            } else {
                $newMainVariant = array_shift($secondParameterValuesWithProducts);
                $createdNewMainVariant = $this->productVariantFacade->createVariant($newMainVariant, $secondParameterValuesWithProducts);
                $this->productFacade->updateCzechProductNamesWithColor($createdNewMainVariant, (string)$colorValue);
                $mainVariants[] = $createdNewMainVariant;
            }
        }

        if ($existingMainVariantGroup !== null) {
            $this->mainVariantGroupFacade->updateMainVariantGroup($existingMainVariantGroup, $mainVariants);
        } else {
            $this->mainVariantGroupFacade->createMainVariantGroup($this->parameterFacade->getColorParameter(), $mainVariants);
        }
    }

    /**
     * @param \App\Model\Product\Product|null $mainVariant
     * @return \App\Model\Product\MainVariantGroup\MainVariantGroup|null
     */
    private function findMainVariantGroup(?Product $mainVariant): ?MainVariantGroup
    {
        return $mainVariant !== null ? $mainVariant->getMainVariantGroup() : null;
    }

    /**
     * @param \App\Model\Product\Product $variant
     */
    private function updateIfProductIsVariant(Product $variant): void
    {
        if ($variant->isVariant() === false) {
            return;
        }

        $this->productVariantFacade->removeVariant($variant);
    }
}
