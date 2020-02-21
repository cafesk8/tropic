<?php

declare(strict_types=1);

namespace App\Model\Order\Gift;

use App\Model\Pricing\Currency\CurrencyFacade;
use Doctrine\ORM\EntityManagerInterface;

class OrderGiftFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    protected $entityManager;

    /**
     * @var \App\Model\Pricing\Currency\CurrencyFacade
     */
    protected $currencyFacade;

    /**
     * @var \App\Model\Order\Gift\OrderGiftRepository
     */
    protected $orderGiftRepository;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $entityManager
     * @param \App\Model\Order\Gift\OrderGiftRepository $orderGiftRepository
     * @param \App\Model\Pricing\Currency\CurrencyFacade $currencyFacade
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        OrderGiftRepository $orderGiftRepository,
        CurrencyFacade $currencyFacade
    ) {
        $this->entityManager = $entityManager;
        $this->currencyFacade = $currencyFacade;
        $this->orderGiftRepository = $orderGiftRepository;
    }

    /**
     * @param \App\Model\Order\Gift\OrderGiftData $orderGiftData
     * @return \App\Model\Order\Gift\OrderGift
     */
    public function create(OrderGiftData $orderGiftData): OrderGift
    {
        $orderGift = new OrderGift($orderGiftData);

        $this->entityManager->persist($orderGift);
        $this->entityManager->flush($orderGift);

        return $orderGift;
    }

    /**
     * @param \App\Model\Order\Gift\OrderGift $orderGift
     * @param \App\Model\Order\Gift\OrderGiftData $orderGiftData
     */
    public function edit(OrderGift $orderGift, OrderGiftData $orderGiftData)
    {
        $orderGift->edit($orderGiftData);
        $this->entityManager->flush($orderGift);
    }

    /**
     * @param int $id
     * @return \App\Model\Order\Gift\OrderGift
     */
    public function getById(int $id): OrderGift
    {
        return $this->orderGiftRepository->getById($id);
    }

    /**
     * @param int $id
     */
    public function delete(int $id)
    {
        $orderGift = $this->getById($id);
        $this->entityManager->remove($orderGift);
        $this->entityManager->flush($orderGift);
    }
}
