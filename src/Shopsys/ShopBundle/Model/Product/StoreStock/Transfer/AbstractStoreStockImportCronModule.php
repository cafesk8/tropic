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

abstract class AbstractStoreStockImportCronModule extends AbstractTransferImportCronModule
{
    /**
     * @var \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    protected $restClient;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\StoreStock\Transfer\StoreStockTransferMapper
     */
    protected $storeStockTransferMapper;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\StoreStock\Transfer\StoreStockTransferValidator
     */
    protected $storeStockTransferValidator;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    protected $productFacade;

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
    abstract protected function getApiUrl(): string;

    /**
     * @return \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponse(): TransferResponse
    {
        $this->logger->addInfo('Starting downloading stock quantities from IS');

        $restResponse = $this->restClient->get($this->getApiUrl());

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
                sprintf('Invalid argument passed into method. Instance of `%s` was expected', StoreStockTransferResponseItemData::class)
            );
        }

        $this->storeStockTransferValidator->validate($storeStockTransferResponseItemData);

        $product = $this->productFacade->findOneNotMainVariantByEan($storeStockTransferResponseItemData->getBarcode());

        if ($product === null) {
            $this->logger->addError(
                sprintf('Product with EAN `%s` has not been found while updating store stock quantities', $storeStockTransferResponseItemData->getBarcode())
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
}
