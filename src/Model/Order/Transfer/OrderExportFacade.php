<?php

declare(strict_types=1);

namespace App\Model\Order\Transfer;

use App\Component\Transfer\Logger\TransferLoggerFactory;
use App\Component\Transfer\Mail\TransferMailFacade;
use App\Component\Transfer\Pohoda\Exception\PohodaInvalidDataException;
use App\Component\Transfer\Pohoda\Exception\PohodaMServerException;
use App\Component\Transfer\Pohoda\MServer\MServerClient;
use App\Component\Transfer\Pohoda\Order\PohodaOrderValidator;
use App\Component\Transfer\Pohoda\Response\PohodaResponse;
use App\Model\Customer\Transfer\CustomerExportFacade;
use App\Model\Order\OrderFacade;
use App\Model\Pricing\Vat\VatFacade;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Model\Mail\Exception\MailException;

class OrderExportFacade
{
    private const ORDERS_EXPORT_MAX_BATCH_LIMIT = 1000;

    /**
     * @var \App\Model\Customer\Transfer\CustomerExportFacade
     */
    private $customerExportFacade;

    /**
     * @var \App\Model\Order\OrderFacade
     */
    private $orderFacade;

    /**
     * @var \App\Model\Order\Transfer\PohodaOrderMapper
     */
    private $pohodaOrderMapper;

    /**
     * @var \App\Model\Pricing\Vat\VatFacade
     */
    private $vatFacade;

    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @var \App\Component\Transfer\Pohoda\Order\PohodaOrderValidator
     */
    private $pohodaOrderValidator;

    /**
     * @var \App\Component\Transfer\Logger\TransferLogger
     */
    private $logger;

    /**
     * @var \App\Component\Transfer\Pohoda\MServer\MServerClient
     */
    private $mServerClient;

    private TransferMailFacade $transferMailFacade;

    /**
     * @param \App\Component\Transfer\Logger\TransferLoggerFactory $transferLoggerFactory
     * @param \App\Model\Customer\Transfer\CustomerExportFacade $customerExportFacade
     * @param \App\Model\Order\OrderFacade $orderFacade
     * @param \App\Model\Order\Transfer\PohodaOrderMapper $pohodaOrderMapper
     * @param \App\Model\Pricing\Vat\VatFacade $vatFacade
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Component\Transfer\Pohoda\Order\PohodaOrderValidator $pohodaOrderValidator
     * @param \App\Component\Transfer\Pohoda\MServer\MServerClient $mServerClient
     * @param \App\Component\Transfer\Mail\TransferMailFacade $transferMailFacade
     */
    public function __construct(
        TransferLoggerFactory $transferLoggerFactory,
        CustomerExportFacade $customerExportFacade,
        OrderFacade $orderFacade,
        PohodaOrderMapper $pohodaOrderMapper,
        VatFacade $vatFacade,
        Domain $domain,
        PohodaOrderValidator $pohodaOrderValidator,
        MServerClient $mServerClient,
        TransferMailFacade $transferMailFacade
    ) {
        $this->logger = $transferLoggerFactory->getTransferLoggerByIdentifier(OrderExportCronModule::TRANSFER_IDENTIFIER);

        $this->customerExportFacade = $customerExportFacade;
        $this->orderFacade = $orderFacade;
        $this->pohodaOrderMapper = $pohodaOrderMapper;
        $this->vatFacade = $vatFacade;
        $this->domain = $domain;
        $this->pohodaOrderValidator = $pohodaOrderValidator;
        $this->mServerClient = $mServerClient;
        $this->transferMailFacade = $transferMailFacade;
    }

    public function processExport(): void
    {
        $orders = $this->orderFacade->getAllForTransfer(self::ORDERS_EXPORT_MAX_BATCH_LIMIT);

        $customersUsers = [];
        foreach ($orders as $order) {
            if ($order->getCustomerUser() !== null) {
                $customersUsers[$order->getCustomerUser()->getId()] = $order->getCustomerUser();
            }
        }

        $this->customerExportFacade->processExportCustomersUsers($customersUsers);
        $this->processExportOrders($orders);
    }

    /**
     * @param \App\Model\Order\Order[] $orders
     */
    private function processExportOrders(array $orders): void
    {
        $pohodaVatNames = $this->getPohodaVatNamesPreparedForPohodaIndexedByDomainId();
        $pohodaOrders = [];
        foreach ($orders as $order) {
            $pohodaOrders[] = $this->pohodaOrderMapper->mapOrderToPohodaOrder($order, $pohodaVatNames);
        }

        $validPohodaOrders = [];
        foreach ($pohodaOrders as $pohodaOrder) {
            try {
                $this->pohodaOrderValidator->validate($pohodaOrder);
            } catch (PohodaInvalidDataException $exc) {
                $this->logger->addError('Objednávka nebude exportována', [
                    'orderNumber' => $pohodaOrder->number,
                    'orderId' => $pohodaOrder->eshopId,
                    'exceptionMessage' => $exc->getMessage(),
                ]);

                continue;
            }
            $validPohodaOrders[] = $pohodaOrder;
        }

        if (count($validPohodaOrders) > 0) {
            $this->logger->addInfo('Proběhne export objednávek', [
                'countValidPohodaOrders' => count($validPohodaOrders),
            ]);

            try {
                $exportedPohodaOrders = $this->mServerClient->exportOrders($validPohodaOrders);
                $this->saveExportOrdersResponse($exportedPohodaOrders);
            } catch (PohodaMServerException $exc) {
                $this->logger->addError('Při exportu došlo k chybě', [
                    'exceptionMessage' => $exc->getMessage(),
                ]);
                try {
                    $this->transferMailFacade->sendMailByErrorMessage($exc->getMessage());
                } catch (\Swift_SwiftException | MailException $mailException) {
                    $this->logger->addError('Chyba při odesílání emailové notifikace o chybě mSeveru', [
                        'exceptionMessage' => $mailException->getMessage(),
                    ]);
                }
            }
        } else {
            $this->logger->addInfo('Nejsou žádné objednávky ke zpracování');
        }

        $this->logger->persistTransferIssues();
    }

    /**
     * @return array
     */
    private function getPohodaVatNamesPreparedForPohodaIndexedByDomainId(): array
    {
        $pohodaVatsNames = [];

        foreach ($this->domain->getAllIds() as $domainId) {
            $pohodaVatsNames[$domainId] = $this->vatFacade->getAllPohodaNamesIndexedByVatPercent($domainId);
        }

        return $pohodaVatsNames;
    }

    /**
     * @param \App\Component\Transfer\Pohoda\Order\PohodaOrder[] $pohodaOrders
     */
    private function saveExportOrdersResponse(array $pohodaOrders): void
    {
        foreach ($pohodaOrders as $pohodaOrder) {
            if (!isset($pohodaOrder->orderResponse)) {
                $this->logger->addError('Při exportu objednávky došlo k neznámé chybě', [
                    'orderId' => $pohodaOrder->eshopId,
                ]);
                continue;
            }

            if ($pohodaOrder->orderResponse->responsePackItemState !== PohodaResponse::POHODA_XML_RESPONSE_ITEM_STATE_OK) {
                $this->logger->addError('Při exportu objednávky došlo k chybě', [
                    'orderId' => $pohodaOrder->eshopId,
                    'responsePackItemNote' => (string)$pohodaOrder->orderResponse->responsePackItemNote,
                ]);

                $this->orderFacade->markOrderAsFailedExported($pohodaOrder->eshopId);
                continue;
            }

            if ($pohodaOrder->orderResponse->responseState !== PohodaResponse::POHODA_XML_RESPONSE_ITEM_STATE_OK) {
                $this->logger->addError('Při exportu objednávky došlo k chybě', [
                    'orderId' => $pohodaOrder->eshopId,
                    'responseNotes' => (string)implode('|', $pohodaOrder->orderResponse->responseNotes),
                ]);

                $this->orderFacade->markOrderAsFailedExported($pohodaOrder->eshopId);
                continue;
            }

            $order = $this->orderFacade->getById($pohodaOrder->eshopId);
            if ($pohodaOrder->orderResponse->responsePackItemState === PohodaResponse::POHODA_XML_RESPONSE_ITEM_STATE_OK
                && !empty($pohodaOrder->orderResponse->producedDetailId)) {
                $this->orderFacade->markOrderAsExported($order->getId(), $pohodaOrder->orderResponse->producedDetailId);
            }

            $this->logger->addInfo('Objednávka byla exportována', [
                'orderId' => $order->getId(),
            ]);
        }
    }
}
