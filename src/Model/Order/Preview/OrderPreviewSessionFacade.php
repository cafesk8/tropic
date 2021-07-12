<?php

declare(strict_types=1);

namespace App\Model\Order\Preview;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OrderPreviewSessionFacade
{
    public const TOTAL_PRICE_SESSION_KEY = 'totalCartPrice';
    public const ITEMS_COUNT_SESSION_KEY = 'cartItemsCount';

    private SessionInterface $session;

    /**
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function unsetOrderPreviewInfoFromSession(): void
    {
        $this->session->remove(self::ITEMS_COUNT_SESSION_KEY);
        $this->session->remove(self::TOTAL_PRICE_SESSION_KEY);
    }

    /**
     * @return string|null
     */
    public function getTotalPrice(): ?string
    {
        return $this->session->get(self::TOTAL_PRICE_SESSION_KEY);
    }

    /**
     * @return int|null
     */
    public function getItemsCount(): ?int
    {
        $itemsCount = $this->session->get(self::ITEMS_COUNT_SESSION_KEY);
        if ($itemsCount === null) {
            return null;
        }

        return (int)$itemsCount;
    }

    /**
     * @param string $totalPriceWithVat
     */
    public function setTotalPrice(string $totalPriceWithVat): void
    {
        $this->session->set(self::TOTAL_PRICE_SESSION_KEY, $totalPriceWithVat);
    }

    /**
     * @param int $itemsCount
     */
    public function setItemsCount(int $itemsCount): void
    {
        $this->session->set(self::ITEMS_COUNT_SESSION_KEY, $itemsCount);
    }
}