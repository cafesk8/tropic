<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Transfer;

use Shopsys\FrameworkBundle\Model\Product\ProductCategoryDomainFactory;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade;
use Shopsys\ShopBundle\Component\Rest\RestClient;
use Shopsys\ShopBundle\Component\Transfer\AbstractTransferImportCronModule;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;
use Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency;
use Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade;
use Shopsys\ShopBundle\Model\Product\Parameter\ParameterFacade;
use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Shopsys\ShopBundle\Model\Product\ProductVariantFacade;
use Shopsys\ShopBundle\Model\Product\Transfer\Exception\InvalidProductTransferResponseItemDataException;

class ProductImportCronModule extends AbstractTransferImportCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_products';

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
        ParameterFacade $parameterFacade
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
            if ($parameter->parameter === $productData->distinguishingParameter) {
                $parameterSize = $parameter->parameterValueData->text;
            }

            if ($parameter->parameter === $productData->distinguishingParameterForMainVariantGroup) {
                $parameterColor = $parameter->parameterValueData->text;
            }
        }

        $this->productTree[$parameterColor][$parameterSize] = $product;

        return $product;
    }

    private function createProductTree(): void
    {
        $newMainVariants = [];
        foreach ($this->productTree as $colorValue => $secondParameterValuesWithProducts) {
            $mainVariant = null;
            $notVariants = [];
            foreach ($secondParameterValuesWithProducts as $productBySizeValue) {
                if ($productBySizeValue->isVariant() === true) {
                    $mainVariant = $productBySizeValue->getMainVariant();
                } elseif ($productBySizeValue->isVariant() === false) {
                    $notVariants[] = $productBySizeValue;
                }
            }

            /** @var \Shopsys\ShopBundle\Model\Product\Product $mainVariant */
            if ($mainVariant !== null) {
                foreach ($notVariants as $notVariant) {
                    $mainVariant->addVariant($notVariant, $this->productCategoryDomainFactory);
                }
                if (count($notVariants) > 0) {
                    $this->productFacade->flushMainVariant($mainVariant);
                }
            } else {
                $mainVariant = array_shift($secondParameterValuesWithProducts);
                $newMainVariants[] = $this->productVariantFacade->createVariant($mainVariant, $secondParameterValuesWithProducts);
            }
        }

        if (count($newMainVariants) > 0) {
            $this->mainVariantGroupFacade->createMainVariantGroup($this->parameterFacade->getColorParameter(), $newMainVariants);
        }
    }
}
