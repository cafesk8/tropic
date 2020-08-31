<?php

declare(strict_types=1);

namespace App\Model\GoPay;

use App\Model\Order\Order;
use Doctrine\ORM\EntityManagerInterface;

class GoPayTransactionFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    private $em;

    /**
     * @var \App\Model\GoPay\GoPayTransactionRepository
     */
    private $goPayTransactionRepository;

    /**
     * @var \App\Model\GoPay\GoPayFacadeOnCurrentDomain
     */
    private $goPayFacadeOnCurrentDomain;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $em
     * @param \App\Model\GoPay\GoPayTransactionRepository $goPayTransactionRepository
     * @param \App\Model\GoPay\GoPayFacadeOnCurrentDomain $goPayFacadeOnCurrentDomain
     */
    public function __construct(
        EntityManagerInterface $em,
        GoPayTransactionRepository $goPayTransactionRepository,
        GoPayFacadeOnCurrentDomain $goPayFacadeOnCurrentDomain
    ) {
        $this->em = $em;
        $this->goPayTransactionRepository = $goPayTransactionRepository;
        $this->goPayFacadeOnCurrentDomain = $goPayFacadeOnCurrentDomain;
    }

    /**
     * @param \App\Model\GoPay\GoPayTransactionData $goPayTransactionData
     * @return \App\Model\GoPay\GoPayTransaction
     */
    public function create(GoPayTransactionData $goPayTransactionData): GoPayTransaction
    {
        $goPayTransaction = new GoPayTransaction($goPayTransactionData);

        $this->em->persist($goPayTransaction);
        $this->em->flush($goPayTransaction);

        return $goPayTransaction;
    }

    /**
     * @param \App\Model\Order\Order $order
     * @param  string $goPayId
     * @return \App\Model\GoPay\GoPayTransaction
     */
    public function createNewTransactionByOrder(Order $order, string $goPayId): GoPayTransaction
    {
        $goPayTransactionData = new GoPayTransactionData($goPayId, $order);

        return $this->create($goPayTransactionData);
    }

    /**
     * @param \App\Model\Order\Order $order
     */
    public function updateOrderTransactions(Order $order): void
    {
        if ($order->isGopayPaid()) {
            return;
        }

        $goPayTransactions = $this->goPayTransactionRepository->findAllByOrder($order);
        $goPayResponsesData = $this->goPayFacadeOnCurrentDomain->getPaymentStatusesResponseDataByGoPayTransactionAndDomainId(
            $goPayTransactions,
            $order->getDomainId()
        );
        $toFlush = [];

        foreach ($goPayResponsesData as $goPayStatusResponseData) {
            $goPayStatusResponse = $goPayStatusResponseData->response;
            if (isset($goPayStatusResponse->json['state'])) {
                $goPayTransaction = $goPayStatusResponseData->goPayTransaction;
                $goPayTransaction->setGoPayStatus($goPayStatusResponse->json['state']);
                $toFlush[] = $goPayTransaction;
            }
        }

        $this->em->flush($toFlush);
    }

    /**
     * @param \App\Model\Order\Order $order
     * @return bool
     */
    public function isOrderPaid(Order $order): bool
    {
        return $this->goPayTransactionRepository->isOrderPaid($order);
    }
}
