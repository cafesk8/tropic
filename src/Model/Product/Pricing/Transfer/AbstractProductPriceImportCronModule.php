<?php

declare(strict_types=1);

namespace App\Model\Product\Pricing\Transfer;

use App\Component\Domain\DomainHelper;
use App\Component\Rest\MultidomainRestClient;
use App\Component\Rest\RestClient;
use App\Component\Transfer\AbstractTransferImportCronModule;
use App\Component\Transfer\Response\TransferResponse;
use App\Component\Transfer\Response\TransferResponseItemDataInterface;
use App\Component\Transfer\TransferCronModuleDependency;
use App\Model\Product\ProductFacade;
use App\Model\Product\Transfer\Exception\InvalidProductTransferResponseItemDataException;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroupSettingFacade;
use Shopsys\FrameworkBundle\Model\Product\Pricing\ProductPriceRecalculator;

abstract class AbstractProductPriceImportCronModule extends AbstractTransferImportCronModule
{
    /**
     * @var \App\Component\Rest\MultidomainRestClient
     */
    protected $multidomainRestClient;

    /**
     * @var \App\Model\Product\Pricing\Transfer\ProductPriceTransferValidator
     */
    protected $productPriceTransferValidator;

    /**
     * @var \App\Model\Product\ProductFacade
     */
    protected $productFacade;

    /**
     * @var \App\Model\Product\Pricing\ProductPriceRecalculator
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
     * @param \App\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \App\Component\Rest\MultidomainRestClient $multidomainRestClient
     * @param \App\Model\Product\Pricing\Transfer\ProductPriceTransferValidator $productPriceTransferValidator
     * @param \App\Model\Product\ProductFacade $productFacade
     * @param \App\Model\Product\Pricing\ProductPriceRecalculator $productPriceRecalculator
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
     * @return \App\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponse(): TransferResponse
    {
        $this->logger->addInfo('Downloading product prices for domain with ID `' . DomainHelper::CZECH_DOMAIN . '`');
        $czechTransferDataItems = $this->getTransferItemsFromResponse(DomainHelper::CZECH_DOMAIN, $this->multidomainRestClient->getCzechRestClient());
        $transferDataItems = $czechTransferDataItems;

        $this->logger->addInfo('Downloading product prices for domain with ID `' . DomainHelper::SLOVAK_DOMAIN . '`');
        $slovakTransferDataItems = $this->getTransferItemsFromResponse(DomainHelper::SLOVAK_DOMAIN, $this->multidomainRestClient->getSlovakRestClient());
        $transferDataItems = array_merge($transferDataItems, $slovakTransferDataItems);

        $this->logger->addInfo('Downloading product prices for domain with ID `' . DomainHelper::ENGLISH_DOMAIN . '`');
        $germanTransferDataItems = $this->getTransferItemsFromResponse(DomainHelper::ENGLISH_DOMAIN, $this->multidomainRestClient->getEnglishRestClient());
        $transferDataItems = array_merge($transferDataItems, $germanTransferDataItems);

        return new TransferResponse(200, $transferDataItems);
    }

    /**
     * @param \App\Component\Transfer\Response\TransferResponseItemDataInterface $productTransferResponseItemData
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
                'Product has not been found while updating prices',
                [
                    'EAN' => $productTransferResponseItemData->getBarcode(),
                ]
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
     * @param \App\Component\Rest\RestClient $restClient
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
