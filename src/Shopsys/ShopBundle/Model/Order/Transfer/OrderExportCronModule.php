<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\Transfer;

use Exception;
use Shopsys\ShopBundle\Component\Rest\RestClient;
use Shopsys\ShopBundle\Component\Transfer\AbstractTransferExportCronModule;
use Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse;
use Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency;
use Shopsys\ShopBundle\Model\Order\OrderFacade;

class OrderExportCronModule extends AbstractTransferExportCronModule
{
    private const TRANSFER_IDENTIFIER = 'export_orders';

    /**
     * @var \Shopsys\ShopBundle\Component\Rest\RestClient
     */
    private $restClient;

    /**
     * @var \Shopsys\ShopBundle\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @param \Shopsys\ShopBundle\Component\Transfer\TransferCronModuleDependency $transferCronModuleDependency
     * @param \Shopsys\ShopBundle\Component\Rest\RestClient $restClient
     * @param \Shopsys\ShopBundle\Model\Order\OrderFacade $orderFacade
     */
    public function __construct(
        TransferCronModuleDependency $transferCronModuleDependency,
        RestClient $restClient,
        OrderFacade $orderFacade
    ) {
        parent::__construct($transferCronModuleDependency);
        $this->restClient = $restClient;
        $this->orderFacade = $orderFacade;
    }

    /**
     * @return string
     */
    protected function getTransferIdentifier(): string
    {
        return self::TRANSFER_IDENTIFIER;
    }

    /**
     * @return array
     */
    protected function getDataForExport(): array
    {
        // TODO: Implement getDataForExport() method.
        return [];
    }

    /**
     * @param array $orderData
     * @return \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse
     */
    protected function getTransferResponse(array $orderData): TransferResponse
    {
        $restResponse = $this->restClient->post('api/Eshop/NewOrder', $orderData);

        return new TransferResponse($restResponse->getCode());
    }

    /**
     * @param int|string $orderIdentifier
     * @param \Shopsys\ShopBundle\Component\Transfer\Response\TransferResponse $transferResponse
     */
    protected function markItemAsExported($orderIdentifier, TransferResponse $transferResponse): void
    {
        //$this->orderFacade->markOrderAsExported($orderIdentifier);
    }

    /**
     * @param int|string $orderIdentifier
     * @param \Exception $exception
     */
    protected function markItemAsFailedExported($orderIdentifier, Exception $exception): void
    {
        //$this->orderFacade->markOrderAsFailedExported($orderIdentifier);
    }
}
