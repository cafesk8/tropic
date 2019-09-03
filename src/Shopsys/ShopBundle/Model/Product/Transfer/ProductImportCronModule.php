<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Transfer;

use Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculator;
use Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactory;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade;
use Shopsys\ShopBundle\Component\Rest\RestClient;
use Shopsys\ShopBundle\Component\Transfer\AbstractTransferImportCronModule;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;
use Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency;
use Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup;
use Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade;
use Shopsys\ShopBundle\Model\Product\Pricing\ProductPriceRecalculator;
use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Shopsys\ShopBundle\Model\Product\ProductVariantFacade;
use Shopsys\ShopBundle\Model\Product\Transfer\Exception\InvalidProductTransferResponseItemDataException;

class ProductImportCronModule extends AbstractTransferImportCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_products';
    private const PRODUCT_NUMBER_TO_NOT_IMPORT = '899005';

    /**
     * @var \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    private $restClient;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferMapper
     */
    private $productTransferMapper;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferValidator
     */
    private $productTransferValidator;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade
     */
    private $productVisibilityFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductVariantFacade
     */
    private $productVariantFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade
     */
    private $mainVariantGroupFacade;

    /**
     * @var array
     */
    private $productTree;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactory
     */
    private $productCategoryDomainFactory;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade
     */
    private $parameterFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Pricing\ProductPriceRecalculator
     */
    private $productPriceRecalculator;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Product\Availability\ProductAvailabilityRecalculator
     */
    private $productAvailabilityRecalculator;

    /**
     * @param \Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $restClient
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferMapper $productTransferMapper
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferValidator $productTransferValidator
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade $productVisibilityFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductVariantFacade $productVariantFacade
     * @param \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade $mainVariantGroupFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactory $productCategoryDomainFactory
     * @param \Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade $parameterFacade
     * @param \Shopsys\ShopBundle\Model\Product\Pricing\ProductPriceRecalculator $productPriceRecalculator
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
        ProductCategoryDomainFactory $productCategoryDomainFactory,
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
        $this->productCategoryDomainFactory = $productCategoryDomainFactory;
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
     * @return \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse
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
     * @param \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface $productTransferResponseItemData
     */
    protected function processTransferItemData(TransferResponseItemDataInterface $productTransferResponseItemData): void
    {
        if (!($productTransferResponseItemData instanceof ProductTransferResponseItemData)) {
            throw new InvalidProductTransferResponseItemDataException(
                sprintf('Invalid argument passed into method. Instance of `%s` was expected', ProductTransferResponseItemData::class)
            );
        }

        $this->productTransferValidator->validate($productTransferResponseItemData);

        $this->productTree = [];

        if (count($productTransferResponseItemData->getVariants()) > 0) {
            foreach ($productTransferResponseItemData->getVariants() as $productVariantsItemData) {
                $this->processProductItemWithVariants($productTransferResponseItemData, $productVariantsItemData);
            }

            $this->createProductTree();
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
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferResponseItemData $productTransferResponseItemData
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferResponseItemVariantData|null $productTransferResponseItemVariantData
     * @return \Shopsys\ShopBundle\Model\Product\Product
     */
    private function processProductItemWithVariants(ProductTransferResponseItemData $productTransferResponseItemData, ?ProductTransferResponseItemVariantData $productTransferResponseItemVariantData = null): Product
    {
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
            /** @var \Shopsys\ShopBundle\Model\Product\Product $existingMainVariant */
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
                    $existingMainVariant->addVariant($notVariant, $this->productCategoryDomainFactory);
                }
                $this->productFacade->flushMainVariant($existingMainVariant);
                $mainVariants[] = $existingMainVariant;
            } else {
                $newMainVariant = array_shift($secondParameterValuesWithProducts);
                $mainVariants[] = $this->productVariantFacade->createVariant($newMainVariant, $secondParameterValuesWithProducts);
            }
        }

        if ($existingMainVariantGroup !== null) {
            $this->mainVariantGroupFacade->updateMainVariantGroup($existingMainVariantGroup, $mainVariants);
        } else {
            $this->mainVariantGroupFacade->createMainVariantGroup($this->parameterFacade->getColorParameter(), $mainVariants);
        }
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\Product $mainVariant
     * @return \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroup|null
     */
    private function findMainVariantGroup(Product $mainVariant): ?MainVariantGroup
    {
        return $mainVariant !== null ? $mainVariant->getMainVariantGroup() : null;
    }
}
