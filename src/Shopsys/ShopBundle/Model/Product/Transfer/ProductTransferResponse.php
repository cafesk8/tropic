<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Product\Transfer;

use Shopsys\ShopBundle\Component\Rest\RestClientFactory;
use Shopsys\ShopBundle\Component\Rest\RestResponse;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponseInterface;
use Shopsys\ShopBundle\Component\Transfer\TransferConfig;
use Shopsys\ShopBundle\Model\Transfer\TransferFacade;

class ProductTransferResponse implements TransferResponseInterface
{
    /**
     * @var \Shopsys\ShopBundle\Component\Rest\RestClientFactory
     */
    private $restClientFactory;

    /**
     * @var \Shopsys\ShopBundle\Component\Transfer\TransferConfig
     */
    private $transferConfig;

    /**
     * @var \Shopsys\ShopBundle\Model\Transfer\TransferFacade
     */
    private $transferFacade;

    /**
     * @param \Shopsys\ShopBundle\Component\Rest\RestClientFactory $restClientFactory
     * @param \Shopsys\ShopBundle\Component\Transfer\TransferConfig $transferConfig
     * @param \Shopsys\ShopBundle\Model\Transfer\TransferFacade $transferFacade
     */
    public function __construct(
        RestClientFactory $restClientFactory,
        TransferConfig $transferConfig,
        TransferFacade $transferFacade
    ) {
        $this->restClientFactory = $restClientFactory;
        $this->transferConfig = $transferConfig;
        $this->transferFacade = $transferFacade;
    }

    /**
     * @return \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse
     */
    public function getResponse(): TransferResponse
    {
        $restResponse = $this->downloadData();

        $transferDataItems = [];
        foreach ($restResponse->getData() as $restData) {
            $transferDataItems[] = new ProductTransferResponseItemData($restData);
        }

        return new TransferResponse($restResponse->getCode(), $transferDataItems);
    }

    /**
     * @return \Shopsys\ShopBundle\Component\Rest\RestResponse
     */
    private function downloadData(): RestResponse
    {
        $restClient = $this->restClientFactory->create(
            $this->transferConfig->getHost(),
            $this->transferConfig->getUsername(),
            $this->transferConfig->getPassword()
        );

        $transfer = $this->transferFacade->getByIdentifier(ProductImportCronModule::TRANSFER_IDENTIFIER);

        if ($transfer->getLastStartAt() === null) {
            $restResponse = $restClient->get('/api/Eshop/Articles');
        } else {
            $restResponse = $restClient->get('/api/Eshop/ChangedArticles');
        }

        return $restResponse;
    }
}
