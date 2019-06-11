<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\TransportAndPayment;

use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\TransportAndPayment\FreeTransportAndPaymentFacade as BaseFreeTransportAndPaymentFacade;

class FreeTransportAndPaymentFacade extends BaseFreeTransportAndPaymentFacade
{
    /**
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $productsPriceWithVat
     * @param int $domainId
     * @return int
     */
    public function getPercentsForFreeTransportAndPayment(Money $productsPriceWithVat, int $domainId): int
    {
        if ($this->isFree($productsPriceWithVat, $domainId)) {
            return 100;
        }

        $freeTransportAndPaymentPriceLimitOnDomain = $this->getFreeTransportAndPaymentPriceLimitOnDomain($domainId);

        if ($freeTransportAndPaymentPriceLimitOnDomain === null) {
            return 0;
        }

        return (int)($productsPriceWithVat->divide($freeTransportAndPaymentPriceLimitOnDomain->getAmount(), 2)->multiply(100))->getAmount();
    }
}
