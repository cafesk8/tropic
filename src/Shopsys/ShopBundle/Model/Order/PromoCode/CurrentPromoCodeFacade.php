<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode;

use Shopsys\FrameworkBundle\Model\Order\PromoCode\CurrentPromoCodeFacade as BaseCurrentPromoCodeFacade;

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
        }
    }
}
