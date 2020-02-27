<?php

declare(strict_types=1);

namespace App\Model\Order\Gift;

use App\Model\Pricing\Currency\CurrencyFacade;
use Doctrine\ORM\EntityManagerInterface;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\Group\PricingGroup;
use Shopsys\FrameworkBundle\Model\Product\Product;

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

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $totalProductPriceWithVat
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Product[]
     */
    public function getAllListableGiftProductsByTotalProductPrice(Money $totalProductPriceWithVat, int $domainId, PricingGroup $pricingGroup): array
    {
        return $this->orderGiftRepository->getAllListableGiftProductsByTotalProductPrice($totalProductPriceWithVat, $domainId, $pricingGroup);
    }

    /**
     * @param \App\Model\Product\Product|null $currentOrderGiftProduct
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $totalProductPriceWithVat
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return bool
     */
    public function isOrderGiftProductValid(?Product $currentOrderGiftProduct, Money $totalProductPriceWithVat, int $domainId, PricingGroup $pricingGroup): bool
    {
        return $currentOrderGiftProduct === null || in_array($currentOrderGiftProduct, $this->getAllListableGiftProductsByTotalProductPrice($totalProductPriceWithVat, $domainId, $pricingGroup), true);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $totalProductPriceWithVat
     * @param int $domainId
     * @param \App\Model\Pricing\Group\PricingGroup $pricingGroup
     * @return \App\Model\Product\Product[]
     */
    public function getAllListableNextLevelGiftProductsByTotalProductPrice(Money $totalProductPriceWithVat, int $domainId, PricingGroup $pricingGroup): array
    {
        return $this->orderGiftRepository->getAllListableNextLevelGiftProductsByTotalProductPrice($totalProductPriceWithVat, $domainId, $pricingGroup);
    }

    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $totalProductPriceWithVat
     * @param int $domainId
     * @return \Shopsys\FrameworkBundle\Component\Money\Money|null
     */
    public function getNextLevelDifference(Money $totalProductPriceWithVat, int $domainId): ?Money
    {
        return $this->orderGiftRepository->getNextLevelDifference($totalProductPriceWithVat, $domainId);
    }
}
