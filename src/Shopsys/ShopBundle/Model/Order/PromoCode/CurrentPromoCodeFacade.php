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
}
