<?php

declare(strict_types=1);

namespace App\Model\Order\Discount;

use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Money\Money;

class OrderDiscountLevelFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator
     */
    private $entityManager;

    /**
     * @var \App\Model\Order\Discount\OrderDiscountLevelRepository
     */
    private $orderDiscountLevelRepository;

    /**
     * @param \Shopsys\FrameworkBundle\Component\EntityExtension\EntityManagerDecorator $entityManager
     * @param \App\Model\Order\Discount\OrderDiscountLevelRepository $orderDiscountLevelRepository
     */
    public function __construct(EntityManagerInterface $entityManager, OrderDiscountLevelRepository $orderDiscountLevelRepository)
    {
        $this->entityManager = $entityManager;
        $this->orderDiscountLevelRepository = $orderDiscountLevelRepository;
    }

    /**
     * @param \App\Model\Order\Discount\OrderDiscountLevelData $orderDiscountLevelData
     * @return \App\Model\Order\Discount\OrderDiscountLevel
     */
    public function create(OrderDiscountLevelData $orderDiscountLevelData): OrderDiscountLevel
    {
        $orderDiscountLevel = new OrderDiscountLevel($orderDiscountLevelData);

        $this->entityManager->persist($orderDiscountLevel);
        $this->entityManager->flush($orderDiscountLevel);

        return $orderDiscountLevel;
    }

    /**
     * @param \App\Model\Order\Discount\OrderDiscountLevel $orderDiscountLevel
     * @param \App\Model\Order\Discount\OrderDiscountLevelData $orderDiscountLevelData
     */
    public function edit(OrderDiscountLevel $orderDiscountLevel, OrderDiscountLevelData $orderDiscountLevelData)
    {
        $orderDiscountLevel->edit($orderDiscountLevelData);
        $this->entityManager->flush($orderDiscountLevel);
    }

    /**
     * @param int $id
     * @return \App\Model\Order\Discount\OrderDiscountLevel
     */
    public function getById(int $id): OrderDiscountLevel
    {
        return $this->orderDiscountLevelRepository->getById($id);
    }

    /**
     * @param int $id
     */
    public function delete(int $id)
    {
        $orderDiscountLevel = $this->getById($id);
        $this->entityManager->remove($orderDiscountLevel);
        $this->entityManager->flush($orderDiscountLevel);
    }

    /**
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $exceptLevel
     * @return \Shopsys\FrameworkBundle\Component\Money\Money[]
     */
    public function getAllLevelsOnDomainExceptLevel(int $domainId, ?Money $exceptLevel = null): array
    {
        return $this->orderDiscountLevelRepository->getAllLevelsOnDomainExceptLevel($domainId, $exceptLevel);
    }
}
