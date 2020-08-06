<?php

declare(strict_types=1);

namespace App\Model\Transport;

use DateTime;
use Shopsys\FrameworkBundle\Component\Money\Money;
use Shopsys\FrameworkBundle\Model\Transport\Transport as BaseTransport;
use Shopsys\FrameworkBundle\Model\Transport\TransportPriceFactory as BaseTransportPriceFactory;

class TransportPriceFactory extends BaseTransportPriceFactory
{
    /**
     * @param \App\Model\Transport\Transport $transport
     * @param \Shopsys\FrameworkBundle\Component\Money\Money $price
     * @param int $domainId
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $actionPrice
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $minActionOrderPrice
     * @param \DateTime|null $actionDateFrom
     * @param \DateTime|null $actionDateTo
     * @param bool $actionActive
     * @param \Shopsys\FrameworkBundle\Component\Money\Money|null $minFreeOrderPrice
     * @return \App\Model\Transport\TransportPrice
     */
    public function create(
        BaseTransport $transport,
        Money $price,
        int $domainId,
        ?Money $actionPrice = null,
        ?Money $minActionOrderPrice = null,
        ?DateTime $actionDateFrom = null,
        ?DateTime $actionDateTo = null,
        bool $actionActive = false,
        ?Money $minFreeOrderPrice = null
    ): TransportPrice {
        return new TransportPrice($transport, $price, $domainId, $actionPrice, $minActionOrderPrice, $actionDateFrom, $actionDateTo, $actionActive, $minFreeOrderPrice);
    }
}
