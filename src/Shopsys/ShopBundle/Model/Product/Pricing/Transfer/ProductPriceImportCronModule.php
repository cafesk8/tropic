<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Pricing\Transfer;

use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculator;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Component\Rest\MultidomainRestClient;
use Shopsys\ShopBundle\Component\Rest\RestClient;
use Shopsys\ShopBundle\Component\Rest\RestResponse;
use Shopsys\ShopBundle\Component\Transfer\AbstractTransferImportCronModule;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;
use Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Shopsys\ShopBundle\Model\Product\Transfer\Exception\InvalidProductTransferResponseItemDataException;
use Shopsys\ShopBundle\Model\Transfer\Transfer;

class ProductPriceImportCronModule extends AbstractTransferImportCronModule
{
    public const TRANSFER_IDENTIFIER = 'import_product_prices';

    /**
     * @var \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    private $multidomainRestClient;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Pricing\Transfer\ProductPriceTransferValidator
     */
    private $productPriceTransferValidator;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    private $productFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Pricing\ProductPriceRecalculator
     */
    private $productPriceRecalculator;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    private $pricingGroupSettingFacade;

    /**
     * @param \Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \Shopsys\ShopBundle\Component\Rest\MultidomainRestClient $multidomainRestClient
     * @param \Shopsys\ShopBundle\Model\Product\Pricing\Transfer\ProductPriceTransferValidator $productPriceTransferValidator
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculator $productPriceRecalculator
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        MultidomainRestClient $multidomainRestClient,
        ProductPriceTransferValidator $productPriceTransferValidator,
        ProductFacade $productFacade,
        ProductPriceRecalculator $productPriceRecalculator,
        PricingGroupSettingFacade $pricingGroupSettingFacade
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->multidomainRestClient = $multidomainRestClient;
        $this->productPriceTransferValidator = $productPriceTransferValidator;
        $this->productFacade = $productFacade;
        $this->productPriceRecalculator = $productPriceRecalculator;
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
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
        $this->logger->addInfo('Downloading product prices for domain with ID `' . DomainHelper::CZECH_DOMAIN . '`');
        $czechTransferDataItems = $this->getTransferItemsFromResponse(DomainHelper::CZECH_DOMAIN, $this->multidomainRestClient->getCzechRestClient());
        $transferDataItems = $czechTransferDataItems;

        $this->logger->addInfo('Downloading product prices for domain with ID `' . DomainHelper::SLOVAK_DOMAIN . '`');
        $slovakTransferDataItems = $this->getTransferItemsFromResponse(DomainHelper::SLOVAK_DOMAIN, $this->multidomainRestClient->getSlovakRestClient());
        $transferDataItems = array_merge($transferDataItems, $slovakTransferDataItems);

        $this->logger->addInfo('Downloading product prices for domain with ID `' . DomainHelper::GERMAN_DOMAIN . '`');
        $germanTransferDataItems = $this->getTransferItemsFromResponse(DomainHelper::GERMAN_DOMAIN, $this->multidomainRestClient->getGermanRestClient());
        $transferDataItems = array_merge($transferDataItems, $germanTransferDataItems);

        return new TransferResponse(200, $transferDataItems);
    }

    /**
     * @param \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface $productTransferResponseItemData
     */
    protected function processTransferItemData(TransferResponseItemDataInterface $productTransferResponseItemData): void
    {
        if (!($productTransferResponseItemData instanceof ProductPriceTransferResponseItemData)) {
            throw new InvalidProductTransferResponseItemDataException(
                sprintf('Invalid argument passed into method. Instance of %s was expected', ProductPriceTransferResponseItemData::class)
            );
        }

        $this->productPriceTransferValidator->validate($productTransferResponseItemData);

        $product = $this->productFacade->findOneByEan($productTransferResponseItemData->getBarcode());

        if ($product === null) {
            $this->logger->addError(
                sprintf('Product with EAN `%s` has not been found while updating prices', $productTransferResponseItemData->getBarcode())
            );
            return;
        }

        $pricingGroup = $this->pricingGroupSettingFacade->getDefaultPricingGroupByDomainId($productTransferResponseItemData->getDomainId());

        $actionPrice = null;
        if ($productTransferResponseItemData->isActionPrice()) {
            $actionPrice = $productTransferResponseItemData->getActionPrice();
        }

        $this->productFacade->setActionPriceForProduct($product, $actionPrice, $productTransferResponseItemData->getDomainId());

        $this->productFacade->refreshProductManualInputPricesForDomain(
            $product,
            [$pricingGroup->getId() => $productTransferResponseItemData->getPrice()],
            $productTransferResponseItemData->getDomainId()
        );

        // ProductPriceRecalculator::recalculateOneProductPrices uses cached pricing groups,
        // but after item transfer identity map is cleared, we need to refresh currently cached pricing groups.
        // Otherwise fatal is thrown.
        $this->productPriceRecalculator->refreshAllPricingGroups();

        $this->productPriceRecalculator->recalculateOneProductPrices($product);

        $this->logger->addInfo(sprintf('Prices for product with ID `%s` has been updated and recalculated', $product->getId()));
    }

    /**
     * @return bool
     */
    protected function isNextIterationNeeded(): bool
    {
        return false;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Transfer\Transfer $transfer
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $restClient
     * @return \Shopsys\ShopBundle\Component\Rest\RestResponse
     */
    private function getRestResponse(Transfer $transfer, RestClient $restClient): RestResponse
    {
        if ($transfer->getLastStartAt() === null) {
            return $restClient->get('/api/Eshop/ArticlePrices');
        }

        return $restClient->get('/api/Eshop/ChangedArticlePrices');
    }

    /**
     * @param int $domainId
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $restClient
     * @return array
     */
    private function getTransferItemsFromResponse(int $domainId, RestClient $restClient)
    {
        $transfer = $this->transferFacade->getByIdentifier(self::TRANSFER_IDENTIFIER);

        $transferDataItems = [];
        $restResponse = $this->getRestResponse($transfer, $restClient);
        foreach ($restResponse->getData() as $restData) {
            foreach ($restData as $restDataItem) {
                $transferDataItems[] = new ProductPriceTransferResponseItemData($restDataItem, $domainId);
            }
        }

        return $transferDataItems;
    }
}
