<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Order\PromoCode;

class PromoCodeLimitDataFactory
{
    /**
     * @return \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimitData
     */
    public function create(): PromoCodeLimitData
    {
        return new PromoCodeLimitData();
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimit $promoCodeLimit
     * @return \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimitData
     */
    public function createFromPromoCodeLimit(PromoCodeLimit $promoCodeLimit): PromoCodeLimitData
    {
        $promoCodeLimitData = $this->create();
        $this->fillFromPromoCodeLimit($promoCodeLimitData, $promoCodeLimit);

        return $promoCodeLimitData;
    }

    /**
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimitData $promoCodeLimitData
     * @param \Shopsys\ShopBundle\Model\Order\PromoCode\PromoCodeLimit $promoCodeLimit
     */
    private function fillFromPromoCodeLimit(PromoCodeLimitData $promoCodeLimitData, PromoCodeLimit $promoCodeLimit)
    {
        $promoCodeLimitData->promoCode = $promoCodeLimit->getPromoCode();
        $promoCodeLimitData->objectId = $promoCodeLimit->getObjectId();
        $promoCodeLimitData->type = $promoCodeLimit->getType();
    }
}
