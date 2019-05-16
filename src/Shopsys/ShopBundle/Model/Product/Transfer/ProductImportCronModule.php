<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Transfer;

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
     * @var \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferResponse
     */
    private $productTransferResponse;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferMapper
     */
    private $productTransferMapper;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @param \Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferResponse $productTransferResponse
     * @param \Shopsys\ShopBundle\Model\Product\Transfer\ProductTransferMapper $productTransferMapper
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        ProductTransferResponse $productTransferResponse,
        ProductTransferMapper $productTransferMapper,
        ProductFacade $productFacade
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->productTransferResponse = $productTransferResponse;
        $this->productTransferMapper = $productTransferMapper;
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
        return $this->productTransferResponse->getResponse();
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
}
