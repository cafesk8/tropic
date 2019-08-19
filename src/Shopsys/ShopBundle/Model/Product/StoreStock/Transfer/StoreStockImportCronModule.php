<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\StoreStock\Transfer;

use Shopsys\ShopBundle\Component\Rest\RestClient;
use Shopsys\ShopBundle\Component\Transfer\AbstractTransferImportCronModule;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;
use Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Shopsys\ShopBundle\Model\Product\Transfer\Exception\InvalidProductTransferResponseItemDataException;

class StoreStockImportCronModule extends AbstractTransferImportCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_product_store_stock';
    public const IMPORT_TYPE_IMPORT_ALL = 'import_type_import_all';
    public const IMPORT_TYPE_IMPORT_UPDATES = 'import_type_import_updates';

    /**
     * @var \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    private $restClient;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\StoreStock\Transfer\StoreStockTransferMapper
     */
    private $storeStockTransferMapper;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\StoreStock\Transfer\StoreStockTransferValidator
     */
    private $storeStockTransferValidator;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var string
     */
    private $importType = self::IMPORT_TYPE_IMPORT_ALL;

    /**
     * @param \Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $restClient
     * @param \Shopsys\ShopBundle\Model\Product\StoreStock\Transfer\StoreStockTransferMapper $storeStockTransferMapper
     * @param \Shopsys\ShopBundle\Model\Product\StoreStock\Transfer\StoreStockTransferValidator $storeStockTransferValidator
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        RestClient $restClient,
        StoreStockTransferMapper $storeStockTransferMapper,
        StoreStockTransferValidator $storeStockTransferValidator,
        ProductFacade $productFacade
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->restClient = $restClient;
        $this->storeStockTransferMapper = $storeStockTransferMapper;
        $this->storeStockTransferValidator = $storeStockTransferValidator;
        $this->productFacade = $productFacade;
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

        $this->logger->addInfo('Starting downloading stock quantities from IS');

        if ($transfer->getLastStartAt() === null) {
            $restResponse = $this->restClient->get('/api/Eshop/StockQuantityBySites');
            $this->importType = self::IMPORT_TYPE_IMPORT_ALL;
        } else {
            $restResponse = $this->restClient->get('/api/Eshop/ChangedStockQuantityBySites');
            $this->importType = self::IMPORT_TYPE_IMPORT_UPDATES;
        }

        $transferDataItems = [];
        foreach ($restResponse->getData() as $restData) {
            $transferDataItems[] = new StoreStockTransferResponseItemData($restData);
        }

        return new TransferResponse($restResponse->getCode(), $transferDataItems);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Product\StoreStock\Transfer\StoreStockTransferResponseItemData $storeStockTransferResponseItemData
     */
    protected function processTransferItemData(TransferResponseItemDataInterface $storeStockTransferResponseItemData): void
    {
        if (!($storeStockTransferResponseItemData instanceof StoreStockTransferResponseItemData)) {
            throw new InvalidProductTransferResponseItemDataException(
                'Invalid argument passed into method. Instance of %s was expected',
                StoreStockTransferResponseItemData::class
            );
        }

        $this->storeStockTransferValidator->validate($storeStockTransferResponseItemData);

        $product = $this->productFacade->findByEan($storeStockTransferResponseItemData->getBarcode());

        if ($product === null) {
            $this->logger->addError(
                printf('Product with EAN %s has not been found while updating store stock quantities', $storeStockTransferResponseItemData->getBarcode())
            );
            return;
        }

        $productData = $this->storeStockTransferMapper->mapTransferDataToProductData(
            $storeStockTransferResponseItemData,
            $product,
            $this->logger,
            $this->importType
        );

        $this->productFacade->edit($product->getId(), $productData);

        $this->logger->addInfo(sprintf('Store stock quantities has been updated for product with EAN %s', $storeStockTransferResponseItemData->getBarcode()));
    }

    /**
     * @return bool
     */
    protected function isNextIterationNeeded(): bool
    {
        return false;
    }
}
