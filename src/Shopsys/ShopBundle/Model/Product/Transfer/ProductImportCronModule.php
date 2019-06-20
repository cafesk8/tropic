<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Transfer;

use Exception;
use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade;
use Shopsys\ShopBundle\Component\Rest\RestClient;
use Shopsys\ShopBundle\Component\Transfer\AbstractTransferImportCronModule;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;
use Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency;
use Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade;
use Shopsys\ShopBundle\Model\Product\Product;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Shopsys\ShopBundle\Model\Product\ProductVariantFacade;
use Shopsys\ShopBundle\Model\Product\Transfer\Exception\InvalidProductTransferResponseItemDataException;

class ProductImportCronModule extends AbstractTransferImportCronModule
{
    const TRANSFER_IDENTIFIER = 'import_products';

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
     * @param \Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $restClient
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferMapper $productTransferMapper
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferValidator $productTransferValidator
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade $productVisibilityFacade
     * @param \Shopsys\ShopBundle\Model\Product\ProductVariantFacade $productVariantFacade
     * @param \Shopsys\ShopBundle\Model\Product\MainVariantGroup\MainVariantGroupFacade $mainVariantGroupFacade
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        RestClient $restClient,
        ProductTransferMapper $productTransferMapper,
        ProductTransferValidator $productTransferValidator,
        ProductFacade $productFacade,
        ProductVisibilityFacade $productVisibilityFacade,
        ProductVariantFacade $productVariantFacade,
        MainVariantGroupFacade $mainVariantGroupFacade
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->restClient = $restClient;
        $this->productTransferMapper = $productTransferMapper;
        $this->productTransferValidator = $productTransferValidator;
        $this->productFacade = $productFacade;
        $this->productVisibilityFacade = $productVisibilityFacade;
        $this->productVariantFacade = $productVariantFacade;
        $this->mainVariantGroupFacade = $mainVariantGroupFacade;
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
     * @param \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface $itemData
     */
    protected function processTransferItemData(TransferResponseItemDataInterface $itemData): void
    {
        if (!($itemData instanceof ProductTransferResponseItemData)) {
            throw new InvalidProductTransferResponseItemDataException(
                'Invalid argument passed into method. Instance of %s was expected',
                ProductTransferResponseItemData::class
            );
        }

        $this->productTransferValidator->validate($itemData);

        $this->productTree = [];

        try {
            $this->em->beginTransaction();

            if (count($itemData->getVariants()) > 0) {
                foreach ($itemData->getVariants() as $productVariantsItemData) {
                    $this->processItem($itemData, $productVariantsItemData);
                }

                $this->createProductTree();
            } else {
                $this->processItem($itemData);
            }
            $this->em->commit();
            $this->logger->addInfo(sprintf('Products for group with transfer number %s was created', $itemData->getTransferNumber()));
        } catch (Exception $exception) {
            $this->em->rollback();
            $this->logger->addError($exception->getMessage(), ['exception' => $exception]);
        }
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
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferResponseItemData $itemData
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferResponseItemVariantData|null $productTransferResponseItemVariantData
     * @return \Shopsys\ShopBundle\Model\Product\Product
     */
    private function processItem(ProductTransferResponseItemData $itemData, ?ProductTransferResponseItemVariantData $productTransferResponseItemVariantData = null): Product
    {
        $mainVariantParameterValue = null;
        $variantParameterValue = null;

        $transferNumber = $productTransferResponseItemVariantData ? $productTransferResponseItemVariantData->getTransferNumber() : $itemData->getTransferNumber();
        $product = $this->productFacade->findByTransferNumber($transferNumber);

        $productData = $this->productTransferMapper->mapTransferDataToProductData($transferNumber, $itemData, $productTransferResponseItemVariantData, $product);

        if ($product === null) {
            $product = $this->productFacade->create($productData);
            $this->logger->addInfo(sprintf('Product variant with transfer number %s was created', $transferNumber));
        } else {
            $product = $this->productFacade->edit($product->getId(), $productData);
            $this->logger->addInfo(sprintf('Product with transfer number %s was edited', $transferNumber));
        }

        foreach ($productData->parameters as $parameter) {
            if ($parameter->parameter === $productData->distinguishingParameter) {
                $variantParameterValue = $parameter->parameterValueData->text;
            }

            if ($parameter->parameter === $productData->distinguishingParameterForMainVariantGroup) {
                $mainVariantParameterValue = $parameter->parameterValueData->text;
            }
        }

        $this->productTree[$mainVariantParameterValue][$variantParameterValue][] = $product;

        return $product;
    }

    private function createProductTree(): void
    {
        foreach ($this->productTree as $firstParameterValue => $secondParameterValue) {
            $mainVariants = [];
            foreach ($secondParameterValue as $products) {
                if (count($products) > 1) {
                    $mainVariant = reset($products);
                    array_shift($products);
                    $mainVariants[] = $this->productVariantFacade->createVariant($mainVariant, $products);
                }
            }

            if (count($mainVariants) > 0) {
                $this->mainVariantGroupFacade->createMainVariantGroup($this->productTransferMapper->getColorParameter(), $mainVariants);
            }
        }
    }
}
