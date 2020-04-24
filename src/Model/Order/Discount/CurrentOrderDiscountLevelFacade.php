<?php

declare(strict_types=1);

namespace App\Model\Order\Discount;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CurrentOrderDiscountLevelFacade
{
    private const ACTIVE_ORDER_DISCOUNT_LEVEL_ID = 'activeOrderDiscountLevelId';

    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    private $session;

    /**
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @param int $activeOrderDiscountLevelId
     */
    public function setActiveOrderLevelDiscountId(int $activeOrderDiscountLevelId): void
    {
        $this->session->set(self::ACTIVE_ORDER_DISCOUNT_LEVEL_ID, $activeOrderDiscountLevelId);
    }

    public function unsetActiveOrderLevelDiscount(): void
    {
        $this->session->set(self::ACTIVE_ORDER_DISCOUNT_LEVEL_ID, null);
    }

    /**
     * @return int|null
     */
    public function getActiveOrderLevelDiscountId(): ?int
    {
        return $this->session->get(self::ACTIVE_ORDER_DISCOUNT_LEVEL_ID);
    }
}
