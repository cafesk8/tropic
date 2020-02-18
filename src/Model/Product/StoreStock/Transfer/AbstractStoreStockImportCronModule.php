<?php

declare(strict_types=1);

namespace App\Model\Product\StoreStock\Transfer;

use App\Component\Rest\MultidomainRestClient;
use App\Component\Rest\RestClient;
use App\Component\Transfer\AbstractTransferImportCronModule;
use App\Component\Transfer\Response\TransferResponse;
use App\Component\Transfer\Response\TransferResponseItemDataInterface;
use App\Component\Transfer\TransferCronModuleDependency;
use App\Model\Product\ProductFacade;
use App\Model\Product\Transfer\Exception\InvalidProductTransferResponseItemDataException;

abstract class AbstractStoreStockImportCronModule extends AbstractTransferImportCronModule
{
    public const TRANSFER_IDENTIFIER = '';

    /**
     * @var \App\Component\Rest\MultidomainRestClient
     */
    protected $multidomainRestClient;

    /**
     * @var \App\Model\Product\StoreStock\Transfer\StoreStockTransferMapper
     */
    protected $storeStockTransferMapper;

    /**
     * @var \App\Model\Product\StoreStock\Transfer\StoreStockTransferValidator
     */
    protected $storeStockTransferValidator;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    protected $productFacade;

    /**
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Component\Rest\MultidomainRestClient $multidomainRestClient
     * @param \App\Model\Product\StoreStock\Transfer\StoreStockTransferMapper $storeStockTransferMapper
     * @param \App\Model\Product\StoreStock\Transfer\StoreStockTransferValidator $storeStockTransferValidator
     * @param \App\Model\Product\ProductFacade $productFacade
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        MultidomainRestClient $multidomainRestClient,
        StoreStockTransferMapper $storeStockTransferMapper,
        StoreStockTransferValidator $storeStockTransferValidator,
        ProductFacade $productFacade
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->multidomainRestClient = $multidomainRestClient;
        $this->storeStockTransferMapper = $storeStockTransferMapper;
        $this->storeStockTransferValidator = $storeStockTransferValidator;
        $this->productFacade = $productFacade;
    }

    /**
     * @return string
     */
    abstract protected function getApiUrl(): string;

    /**
     * @return \App\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponse(): TransferResponse
    {
        $this->logger->addInfo('Downloading stock quantities from IS for Czech domain');
        $czechTransferDataItems = $this->getTransferItemsFromResponse($this->multidomainRestClient->getCzechRestClient());
        $transferDataItems = $czechTransferDataItems;

        $this->logger->addInfo('Downloading stock quantities from IS for Slovak domain');
        $slovakTransferDataItems = $this->getTransferItemsFromResponse($this->multidomainRestClient->getSlovakRestClient());
        $transferDataItems = array_merge($transferDataItems, $slovakTransferDataItems);

        $this->logger->addInfo('Downloading stock quantities from IS for German domain');
        $germanTransferDataItems = $this->getTransferItemsFromResponse($this->multidomainRestClient->getGermanRestClient());
        $transferDataItems = array_merge($transferDataItems, $germanTransferDataItems);

        return new TransferResponse(200, $transferDataItems);
    }

    /**
     * @param \App\Model\Product\StoreStock\Transfer\StoreStockTransferResponseItemData $storeStockTransferResponseItemData
     */
    protected function processTransferItemData(TransferResponseItemDataInterface $storeStockTransferResponseItemData): void
    {
        if (!($storeStockTransferResponseItemData instanceof StoreStockTransferResponseItemData)) {
            throw new InvalidProductTransferResponseItemDataException(
                sprintf('Invalid argument passed into method. Instance of `%s` was expected', StoreStockTransferResponseItemData::class)
            );
        }

        $this->storeStockTransferValidator->validate($storeStockTransferResponseItemData);

        $product = $this->productFacade->findOneNotMainVariantByEan($storeStockTransferResponseItemData->getBarcode());

        if ($product === null) {
            $this->logger->addError(
                'Product has not been found while updating store stock quantities',
                [
                    'EAN' => $storeStockTransferResponseItemData->getBarcode(),
                ]
            );
            return;
        }

        $productData = $this->storeStockTransferMapper->mapTransferDataToProductData(
            $storeStockTransferResponseItemData,
            $product,
            $this->logger,
            $this->getTransferIdentifier()
        );

        $this->productFacade->edit($product->getId(), $productData);

        $this->logger->addInfo(sprintf('Store stock quantities has been updated for product with EAN `%s`', $storeStockTransferResponseItemData->getBarcode()));
    }

    /**
     * @return bool
     */
    protected function isNextIterationNeeded(): bool
    {
        return false;
    }

    /**
     * @param \App\Component\Rest\RestClient $restClient
     * @return array
     */
    protected function getTransferItemsFromResponse(RestClient $restClient)
    {
        $transferDataItems = [];
        $restResponse = $restClient->get($this->getApiUrl());
        foreach ($restResponse->getData() as $restData) {
            $transferDataItems[] = new StoreStockTransferResponseItemData($restData);
        }

        return $transferDataItems;
    }

    /**
     * @return string
     */
    protected function getTransferIdentifier(): string
    {
        return static::TRANSFER_IDENTIFIER;
    }
}
