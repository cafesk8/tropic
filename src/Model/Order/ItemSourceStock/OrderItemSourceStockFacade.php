<?php

declare(strict_types=1);

namespace App\Model\Order\ItemSourceStock;

use App\Model\Order\Item\OrderItem;
use Doctrine\ORM\EntityManagerInterface;

class OrderItemSourceStockFacade
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * @var \App\Model\Order\ItemSourceStock\OrderItemSourceStockRepository
     */
    private $orderItemSourceStockRepository;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Model\Order\ItemSourceStock\OrderItemSourceStockRepository $orderItemSourceStockRepository
     */
    public function __construct(EntityManagerInterface $em, OrderItemSourceStockRepository $orderItemSourceStockRepository)
    {
        $this->em = $em;
        $this->orderItemSourceStockRepository = $orderItemSourceStockRepository;
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

    /**
     * @param \App\Model\Order\Item\OrderItem $orderItem
     * @return \App\Model\Order\ItemSourceStock\OrderItemSourceStock[]
     */
    public function getAllByOrderItem(OrderItem $orderItem): array
    {
        return $this->orderItemSourceStockRepository->getAllByOrderItem($orderItem);
    }
}
