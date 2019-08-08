<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode;

use DateTime;
use Shopsys\FrameworkBundle\Component\Domain\Domain;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade as BaseCurrentPromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeFacade;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CurrentPromoCodeFacade extends BaseCurrentPromoCodeFacade
{
    /**
     * @var \Shopsys\FrameworkBundle\Component\Domain\Domain
     */
    private $domain;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeFacade $promoCodeFacade
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     */
    public function __construct(PromoCodeFacade $promoCodeFacade, SessionInterface $session, Domain $domain)
    {
        parent::__construct($promoCodeFacade, $session);
        $this->domain = $domain;
    }

    /**
     * @var \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeFacade
     */
    protected $promoCodeFacade;

    public function useEnteredPromoCode(): void
    {
        $promoCode = $this->getValidEnteredPromoCodeOrNull();

        if ($promoCode instanceof PromoCode) {
            $this->usePromoCode($promoCode);
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode $promoCode
     */
    public function usePromoCode(PromoCode $promoCode): void
    {
        $this->promoCodeFacade->usePromoCode($promoCode);
    }

    /**
     * @param string $enteredCode
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $totalWatchedPriceOfProducts
     */
    public function setEnteredPromoCode($enteredCode, ?Money $totalWatchedPriceOfProducts = null): void
    {
        if ($totalWatchedPriceOfProducts == null) {
            $totalWatchedPriceOfProducts = Money::zero();
        }

        $this->checkPromoCodeValidity($enteredCode, $totalWatchedPriceOfProducts);

        parent::setEnteredPromoCode($enteredCode);
    }

    /**
     * @param string $enteredCode
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $totalWatchedPriceOfProducts
     */
    public function checkPromoCodeValidity(string $enteredCode, Money $totalWatchedPriceOfProducts): void
    {
        /** @var \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode */
        $promoCode = $this->promoCodeFacade->findPromoCodeByCode($enteredCode);

        if ($promoCode === null || $promoCode->getDomainId() !== $this->domain->getId()) {
            throw new \Shopsys\FrameworkBundle\Model\Order\PromoCode\Exception\InvalidPromoCodeException($enteredCode);
        } elseif ($promoCode->hasRemainingUses() === false) {
            throw new \Shopsys\ShopBundle\Model\Order\PromoCode\Exception\UsageLimitPromoCodeException($enteredCode);
        } elseif ($this->isPromoCodeValidInItsValidDates($promoCode) === false) {
            throw new \Shopsys\ShopBundle\Model\Order\PromoCode\Exception\PromoCodeIsNotValidNow($enteredCode);
        } elseif ($promoCode->getMinOrderValue() !== null && $totalWatchedPriceOfProducts->isLessThan($promoCode->getMinOrderValue())) {
            throw new \Shopsys\ShopBundle\Model\Order\PromoCode\Exception\MinimalOrderValueException($enteredCode, $promoCode->getMinOrderValue());
        }
    }

    /**
     * @param \Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode $promoCode
     * @return bool
     */
    private function isPromoCodeValidInItsValidDates(PromoCode $promoCode): bool
    {
        $validFrom = $promoCode->getValidFrom();
        $validTo = $promoCode->getValidTo();
        $now = new DateTime();

        if ($validFrom === null && $validTo === null) {
            return true;
        }

        if ($validFrom !== null && $validTo !== null) {
            return $validFrom < $now && $now < $validTo;
        }

        if ($validFrom !== null && $validTo === null) {
            return $validFrom < $now;
        }

        return $now < $validTo;
    }
}
