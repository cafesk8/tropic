<?php

declare(strict_types=1);

namespace App\Model\TransportAndPayment;

use App\Model\Transport\TransportFacade;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Pricing\PricingSetting;
use Shopsys\FrameworkBundle\Model\TransportAndPayment\FreeTransportAndPaymentFacade as BaseFreeTransportAndPaymentFacade;

class FreeTransportAndPaymentFacade extends BaseFreeTransportAndPaymentFacade
{
    private TransportFacade $transportFacade;

    /**
     * @param \Shopsys\FrameworkBundle\Model\Pricing\PricingSetting $pricingSetting
     * @param \App\Model\Transport\TransportFacade $transportFacade
     */
    public function __construct(PricingSetting $pricingSetting, TransportFacade $transportFacade)
    {
        parent::__construct($pricingSetting);
        $this->transportFacade = $transportFacade;
    }

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

    /**
     * @inheritDoc
     */
    protected function getFreeTransportAndPaymentPriceLimitOnDomain($domainId): ?Money
    {
        return $this->transportFacade->getMinOrderPriceForFreeTransport($domainId);
    }
}
