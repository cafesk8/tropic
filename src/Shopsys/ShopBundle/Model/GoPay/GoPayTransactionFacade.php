<?php

declare(strict_types = 1);

namespace Shopsys\ShopBundle\Model\GoPay;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\ShopBundle\Model\Order\Order;

class GoPayTransactionFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \Shopsys\ShopBundle\Model\GoPay\GoPayTransactionRepository
     */
    private $goPayTransactionRepository;

    /**
     * @var \Shopsys\ShopBundle\Model\GoPay\GoPayFacadeOnCurrentDomain
     */
    private $goPayFacadeOnCurrentDomain;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Shopsys\ShopBundle\Model\GoPay\GoPayTransactionRepository $goPayTransactionRepository
     * @param \Shopsys\ShopBundle\Model\GoPay\GoPayFacadeOnCurrentDomain $goPayFacadeOnCurrentDomain
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
     * @param \Shopsys\ShopBundle\Model\GoPay\GoPayTransactionData $goPayTransactionData
     * @return \Shopsys\ShopBundle\Model\GoPay\GoPayTransaction
     */
    public function create(GoPayTransactionData $goPayTransactionData): GoPayTransaction
    {
        $goPayTransaction = new GoPayTransaction($goPayTransactionData);

        $this->em->persist($goPayTransaction);
        $this->em->flush($goPayTransaction);

        return $goPayTransaction;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @param  string $goPayId
     * @return \Shopsys\ShopBundle\Model\GoPay\GoPayTransaction
     */
    public function createNewTransactionByOrder(Order $order, string $goPayId): GoPayTransaction
    {
        $goPayTransactionData = new GoPayTransactionData($goPayId, $order);

        return $this->create($goPayTransactionData);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
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
            if (array_key_exists('state', $goPayStatusResponse->json)) {
                $goPayTransaction = $goPayStatusResponseData->goPayTransaction;
                $goPayTransaction->setGoPayStatus($goPayStatusResponse->json['state']);
                $toFlush[] = $goPayTransaction;
            }
        }

        $this->em->flush($toFlush);
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\Order $order
     * @return bool
     */
    public function isOrderPaid(Order $order): bool
    {
        return $this->goPayTransactionRepository->isOrderPaid($order);
    }
}
