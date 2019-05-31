<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Transfer;

use Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade;
use Shopsys\ShopBundle\Component\Rest\RestClient;
use Shopsys\ShopBundle\Component\Transfer\AbstractTransferImportCronModule;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;
use Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
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
     * @param \Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $restClient
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferMapper $productTransferMapper
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferValidator $productTransferValidator
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\ProductVisibilityFacade $productVisibilityFacade
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        RestClient $restClient,
        ProductTransferMapper $productTransferMapper,
        ProductTransferValidator $productTransferValidator,
        ProductFacade $productFacade,
        ProductVisibilityFacade $productVisibilityFacade
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->restClient = $restClient;
        $this->productTransferMapper = $productTransferMapper;
        $this->productTransferValidator = $productTransferValidator;
        $this->productFacade = $productFacade;
        $this->productVisibilityFacade = $productVisibilityFacade;
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

        $product = $this->productFacade->findByTransferNumber($itemData->getNumber());

        $productData = $this->productTransferMapper->mapTransferDataToProductData($itemData, $product);

        if ($product === null) {
            $this->productFacade->create($productData);
            $this->logger->addInfo(sprintf('Product with transfer number %s was created', $itemData->getNumber()));
        } else {
            $this->productFacade->edit($product->getId(), $productData);
            $this->logger->addInfo(sprintf('Product with transfer number %s was edited', $itemData->getNumber()));
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
}
