<?php

declare(strict_types=1);

namespace App\Model\Transport;

use Shopsys\FrameworkBundle\Model\Transport\Transport;
use Shopsys\FrameworkBundle\Model\Transport\TransportVisibilityCalculation as BaseTransportVisibilityCalculation;

/**
 * @method bool existsIndependentlyVisiblePaymentWithTransport(\App\Model\Payment\Payment[] $payments, \App\Model\Transport\Transport $transport, int $domainId)
 * @method \App\Model\Transport\Transport[] filterVisible(\App\Model\Transport\Transport[] $transports, \App\Model\Payment\Payment[] $visiblePaymentsOnDomain, int $domainId)
 * @property \App\Model\Payment\IndependentPaymentVisibilityCalculation $independentPaymentVisibilityCalculation
 * @method __construct(\Shopsys\FrameworkBundle\Model\Transport\IndependentTransportVisibilityCalculation $independentTransportVisibilityCalculation, \App\Model\Payment\IndependentPaymentVisibilityCalculation $independentPaymentVisibilityCalculation)
 */
class TransportVisibilityCalculation extends BaseTransportVisibilityCalculation
{
    /**
     * @param \App\Model\Transport\Transport $transport
     * @param \App\Model\Payment\Payment[] $allPaymentsOnDomain
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
