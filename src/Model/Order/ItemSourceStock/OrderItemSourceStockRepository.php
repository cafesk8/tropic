<?php

declare(strict_types=1);

namespace App\Model\Order\ItemSourceStock;

use App\Model\Order\Item\OrderItem;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class OrderItemSourceStockRepository
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
     * @return \Doctrine\ORM\EntityRepository
     */
    private function getOrderItemSourceStockRepository(): EntityRepository
    {
        return $this->em->getRepository(OrderItemSourceStock::class);
    }

    /**
     * @param \App\Model\Order\Item\OrderItem $orderItem
     * @return \App\Model\Order\ItemSourceStock\OrderItemSourceStock[]
     */
    public function getAllByOrderItem(OrderItem $orderItem): array
    {
        return $this->getOrderItemSourceStockRepository()->findBy(['orderItem' => $orderItem]);
    }
}
