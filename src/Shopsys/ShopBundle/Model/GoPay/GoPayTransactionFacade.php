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
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct(
        EntityManagerInterface $em
    ) {
        $this->em = $em;
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
}
