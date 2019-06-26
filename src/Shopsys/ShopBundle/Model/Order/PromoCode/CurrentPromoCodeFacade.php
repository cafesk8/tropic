<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode;

use DateTime;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade as BaseCurrentPromoCodeFacade;
use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCode;

class CurrentPromoCodeFacade extends BaseCurrentPromoCodeFacade
{
    /**
     * @var \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeFacade
     */
    protected $promoCodeFacade;

    public function useEnteredPromoCode(): void
    {
        $promoCode = $this->getValidEnteredPromoCodeOrNull();

        if ($promoCode instanceof PromoCode) {
            $this->promoCodeFacade->usePromoCode($promoCode);
        }
    }

    /**
     * @param string $enteredCode
     */
    public function setEnteredPromoCode($enteredCode): void
    {
        $this->checkPromoCodeValidity($enteredCode);

        parent::setEnteredPromoCode($enteredCode);
    }

    /**
     * @param string $enteredCode
     */
    public function checkPromoCodeValidity(string $enteredCode): void
    {
        /** @var \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode */
        $promoCode = $this->promoCodeFacade->findPromoCodeByCode($enteredCode);

        if ($promoCode === null) {
            throw new \Shopsys\FrameworkBundle\Model\Order\PromoCode\Exception\InvalidPromoCodeException($enteredCode);
        } elseif ($promoCode->hasRemainingUses() === false) {
            throw new \Shopsys\ShopBundle\Model\Order\PromoCode\Exception\UsageLimitPromoCodeException($enteredCode);
        } elseif ($this->isPromoCodeValidInItsValidDates($promoCode) === false) {
            throw new \Shopsys\ShopBundle\Model\Order\PromoCode\Exception\PromoCodeIsNotValidNow($enteredCode);
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
