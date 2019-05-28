<?php

declare(strict_types=1);

namespace Shopsys\ShopBundle\Model\Transport;


use Shopsys\FrameworkBundle\Model\Transport\Transport;
use Shopsys\FrameworkBundle\Model\Transport\TransportVisibilityCalculation as BaseTransportVisibilityCalculation;

class TransportVisibilityCalculation extends BaseTransportVisibilityCalculation
{
    /**
     * @param \Shopsys\ShopBundle\Model\Transport\Transport $transport
     * @param \Shopsys\FrameworkBundle\Model\Payment\Payment[] $allPaymentsOnDomain
     * @param int $domainId
     * @return bool
     */
    public function isVisible(Transport $transport, array $allPaymentsOnDomain, $domainId): bool
    {
        if (!$this->independentTransportVisibilityCalculation->isIndependentlyVisible($transport, $domainId)) {
            return false;
        }

        if ($transport->isInitialDownload() === true) {
            return false;
        }

        return $this->existsIndependentlyVisiblePaymentWithTransport($allPaymentsOnDomain, $transport, $domainId);
    }
}
