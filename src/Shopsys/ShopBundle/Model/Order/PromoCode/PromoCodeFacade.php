<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode;

use Shopsys\FrameworkBundle\Model\Order\PromoCode\PromoCodeFacade as BasePromoCodeFacade;

class PromoCodeFacade extends BasePromoCodeFacade
{
    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCode $promoCode
     */
    public function usePromoCode(PromoCode $promoCode): void
    {
        $promoCode->addUsage();
        $this->em->flush($promoCode);
    }
}
