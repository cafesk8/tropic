<?php

declare(strict_types=1);

namespace App\Component\Cofidis\Banner;

use App\Component\Domain\DomainHelper;
use App\Component\Setting\Setting;
use App\Model\Payment\Payment;
use App\Model\Payment\PaymentFacade;
use App\Model\Product\Pricing\ProductPrice;
use Shopsys\FrameworkBundle\Component\Domain\Domain;

class CofidisBannerFacade
{
    private Setting $setting;

    private Domain $domain;

    private PaymentFacade $paymentFacade;

    /**
     * @param \App\Component\Setting\Setting $setting
     * @param \Shopsys\FrameworkBundle\Component\Domain\Domain $domain
     * @param \App\Model\Payment\PaymentFacade $paymentFacade
     */
    public function __construct(Setting $setting, Domain $domain, PaymentFacade $paymentFacade)
    {
        $this->setting = $setting;
        $this->domain = $domain;
        $this->paymentFacade = $paymentFacade;
    }

    /**
     * @param \App\Model\Product\Pricing\ProductPrice $productPrice
     * @return bool
     */
    public function isAllowedToShowCofidisBanner(ProductPrice $productPrice): bool
    {
        if (!DomainHelper::isCzechDomain($this->domain)) {
            return false;
        }

        $minimumBannerShowProductPrice = $this->setting->getForDomain(Setting::COFIDIS_BANNER_MINIMUM_SHOW_PRICE_ID, $this->domain->getId());
        $cofidisPayment = $this->paymentFacade->getFirstPaymentByType(Payment::TYPE_COFIDIS);

        return $cofidisPayment !== null
            && $minimumBannerShowProductPrice !== null
            && $cofidisPayment->isEnabled($this->domain->getId())
            && $productPrice->getPriceWithVat()->isGreaterThanOrEqualTo($minimumBannerShowProductPrice);
    }
}
