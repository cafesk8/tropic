<?php

declare(strict_types=1);

namespace App\Model\Order\ItemSourceStock;

use Doctrine\ORM\EntityManagerInterface;

class OrderItemSourceStockFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param \App\Model\Order\ItemSourceStock\OrderItemSourceStockData $orderItemSourceStockData
     * @return \App\Model\Order\ItemSourceStock\OrderItemSourceStock
     */
    public function create(OrderItemSourceStockData $orderItemSourceStockData): OrderItemSourceStock
    {
        $orderItemSourceStock = new OrderItemSourceStock($orderItemSourceStockData);
        $this->em->persist($orderItemSourceStock);
        $this->em->flush();

        return $orderItemSourceStock;
    }
}
