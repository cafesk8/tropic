<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Pricing\Transfer;

use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculator;
use Shopsys\ShopBundle\Component\Domain\DomainHelper;
use Shopsys\ShopBundle\Component\Rest\MultidomainRestClient;
use Shopsys\ShopBundle\Component\Rest\RestClient;
use Shopsys\ShopBundle\Component\Transfer\AbstractTransferImportCronModule;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseItemDataInterface;
use Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency;
use Shopsys\ShopBundle\Model\Product\ProductFacade;
use Shopsys\ShopBundle\Model\Product\Transfer\Exception\InvalidProductTransferResponseItemDataException;

abstract class AbstractProductPriceImportCronModule extends AbstractTransferImportCronModule
{
    /**
     * @var \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    protected $multidomainRestClient;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Pricing\Transfer\ProductPriceTransferValidator
     */
    protected $productPriceTransferValidator;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\ProductFacade
     */
    protected $productFacade;

    /**
     * @var \Shopsys\ShopBundle\Model\Product\Pricing\ProductPriceRecalculator
     */
    protected $productPriceRecalculator;

    /**
     * @var \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade
     */
    protected $pricingGroupSettingFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    protected $domain;

    /**
     * @param \Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \Shopsys\ShopBundle\Component\Rest\MultidomainRestClient $multidomainRestClient
     * @param \Shopsys\ShopBundle\Model\Product\Pricing\Transfer\ProductPriceTransferValidator $productPriceTransferValidator
     * @param \Shopsys\ShopBundle\Model\Product\ProductFacade $productFacade
     * @param \Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculator $productPriceRecalculator
     * @param \Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade $pricingGroupSettingFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        MultidomainRestClient $multidomainRestClient,
        ProductPriceTransferValidator $productPriceTransferValidator,
        ProductFacade $productFacade,
        ProductPriceRecalculator $productPriceRecalculator,
        PricingGroupSettingFacade $pricingGroupSettingFacade,
        Domain $domain
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->multidomainRestClient = $multidomainRestClient;
        $this->productPriceTransferValidator = $productPriceTransferValidator;
        $this->productFacade = $productFacade;
        $this->productPriceRecalculator = $productPriceRecalculator;
        $this->pricingGroupSettingFacade = $pricingGroupSettingFacade;
        $this->domain = $domain;
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

        $product = $this->productFacade->findOneNotMainVariantByEan($productTransferResponseItemData->getBarcode());

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

    public function end(): void
    {
        foreach ($this->domain->getAllIds() as $domainId) {
            $hiddenVariantIds = $this->productFacade->hideVariantsWithDifferentPriceForDomain($domainId);
            $this->logger->addWarning('Variants were hidden because of different price.', [
                'variants' => $hiddenVariantIds,
                'domainId' => $domainId,
            ]);
        }
        parent::end();
    }

    /**
     * @param int $domainId
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $restClient
     * @return array
     */
    protected function getTransferItemsFromResponse(int $domainId, RestClient $restClient)
    {
        $transferDataItems = [];
        $restResponse = $restClient->get($this->getApiUrl());
        foreach ($restResponse->getData() as $restData) {
            foreach ($restData as $restDataItem) {
                $transferDataItems[] = new ProductPriceTransferResponseItemData($restDataItem, $domainId);
            }
        }

        return $transferDataItems;
    }
}
